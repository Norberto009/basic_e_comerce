<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = Order::query()
            ->with(['user', 'items.product'])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return OrderResource::collection($orders);
    }

    public function byUser(Request $request, User $user): AnonymousResourceCollection
    {
        $orders = $user->orders()
            ->with(['user', 'items.product'])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return OrderResource::collection($orders);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = DB::transaction(function () use ($request) {
            $items = collect($request->validated('items'));

            $products = Product::whereIn('id', $items->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => ["Product #{$item['product_id']} is not available."],
                    ]);
                }

                if ($product->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => ["Insufficient stock for product \"{$product->name}\"."],
                    ]);
                }
            }

            $order = Order::create([
                'user_id' => $request->user()->id,
                'status' => 'pending',
                'total' => 0,
                'currency' => 'usd',
            ]);

            $total = 0;

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                $subtotal = $product->price * $item['quantity'];
                $total += $subtotal;

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal' => $subtotal,
                ]);

                $product->decrement('stock', $item['quantity']);
            }

            $order->update(['total' => $total]);

            return $order;
        });

        $order->load('items.product');

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Order $order): OrderResource|JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to view this order.',
            ], 403);
        }

        $order->load('user', 'items.product', 'payment');

        return new OrderResource($order);
    }
}
