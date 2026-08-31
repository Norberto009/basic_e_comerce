<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $producto = Producto::all();
            
            return response()->json([
                'data'=> $producto
            ]);
            
        } catch (\Exception $error){
            return response()->json([
                'message' => $error->getMessage()
                ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name_product'=>'required|strign',
                'price'=>'required|numeric|between:0,99999.99',
                'descuento'=>'required|numeric|between:0,99999.99'
            ]);

            $producto = Producto::create([
                'name_product'=> $request->name_product,
                'price'=> $request->price,
                'descuento'=> $request->descuento

            ]);

            return response()->json([
                'message' => 'Product created successfully',
                'data' => $producto
            ],201);

        } catch (\Exception $error){
            return response()->json([
                'message' => $error->getMessage()
                ]);
        }
    }

  

   

    /**
     * Show the form for editing the specified resource.
     */
    public function update(Producto $request, string $id)
    {
        try {
            $producto = Producto::findOrFail($id);

            $request->validate([
                'name_product'=>'required|strign',
                'price'=>'required|numeric|between:0,99999.99',
                'descuento'=>'required|numeric|between:0,99999.99'
            ]);

            $producto->update($request->all());

            return response()->json([
                'message' => 'Post updated successfully',
                'data' => $producto
            ]);

            
        } catch(\Exception $error){
            return response()->json([
                'message' => $error->getMessage()
                ]);
        }
    }

    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $producto = Producto::findOrFail($id);

            $producto->delete();

            return response()->json([
                'message' => 'Post deleted successfully'
            ]);
        } catch(\Exception $error){
            return response()->json([
                'message' => $error->getMessage()
                ]);
        }
    }
}
