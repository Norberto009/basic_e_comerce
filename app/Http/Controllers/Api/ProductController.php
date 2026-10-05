<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Product::query()->with(['createdBy', 'updatedBy']);

        if ($search = $request->query('search')) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $products = $query->latest()->paginate($request->integer('per_page', 15));

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['is_active'] = $data['is_active'] ?? true;
        $data['created_by'] = $request->user()->id;

        $product = Product::create($data);
        $product->load(['createdBy', 'updatedBy']);

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        $product->load(['createdBy', 'updatedBy']);

        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $data = $request->validated();

        if (isset($data['name']) && $data['name'] !== $product->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $product->id);
        }

        $data['updated_by'] = $request->user()->id;

        $product->update($data);
        $product->load(['createdBy', 'updatedBy']);

        return new ProductResource($product);
    }

    public function destroy(Request $request, Product $product)
    {
        $product->update(['deleted_by' => $request->user()->id]);
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }

    public function restore(Request $request, Product $product): ProductResource|JsonResponse
    {
        if (! $product->trashed()) {
            return response()->json([
                'message' => 'This product is not deleted.',
            ], 422);
        }

        $product->restore();
        $product->update([
            'updated_by' => $request->user()->id,
            'deleted_by' => null,
        ]);
        $product->load(['createdBy', 'updatedBy']);

        return new ProductResource($product);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (
            Product::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter++;
        }

        return $slug;
    }
}
