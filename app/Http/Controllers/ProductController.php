<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * GET /api/products
     * Display a listing of the products.
     */
    public function index()
    {
        $products = Product::orderByDesc('id')->get();

        return response()->json([
            'data' => $products,
        ]);
    }

    /**
     * POST /api/products
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'brand'       => 'nullable|string|max:255',
            'category'    => 'nullable|string|max:255',
            'color'       => 'nullable|string|max:255',
            'size_range'  => 'nullable|string|max:255',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'image_url'   => 'nullable|string|max:2048',
            'description' => 'nullable|string',
            // sold_count dikelola oleh sistem saat transaksi
        ]);

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Product created successfully.',
            'data'    => $product,
        ], 201);
    }

    /**
     * GET /api/products/{id}
     * Display the specified product.
     */
    public function show(string $id)
    {
        $product = Product::findOrFail($id);

        return response()->json([
            'data' => $product,
        ]);
    }

    /**
     * PUT /api/products/{id}
     * Update the specified product.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'brand'       => 'sometimes|nullable|string|max:255',
            'category'    => 'sometimes|nullable|string|max:255',
            'color'       => 'sometimes|nullable|string|max:255',
            'size_range'  => 'sometimes|nullable|string|max:255',
            'price'       => 'sometimes|required|numeric|min:0',
            'stock'       => 'sometimes|required|integer|min:0',
            'image_url'   => 'sometimes|nullable|string|max:2048',
            'description' => 'sometimes|nullable|string',
            // sold_count tidak bisa diupdate langsung dari sini
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully.',
            'data'    => $product,
        ]);
    }

    /**
     * DELETE /api/products/{id}
     * Remove the specified product.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }
}
