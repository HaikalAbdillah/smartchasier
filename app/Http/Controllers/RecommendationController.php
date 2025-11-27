<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecommendationController extends Controller
{
    /**
     * GET /api/recommendations
     *
     * - Tanpa query ?product_id=ID  → rekomendasi top seller.
     * - Dengan query ?product_id=ID → rekomendasi rule-based berdasarkan brand.
     */
    public function index(Request $request)
    {
        $productId = $request->query('product_id');

        if ($productId) {
            return $this->ruleBasedByBrand($request, $productId);
        }

        return $this->topSeller($request);
    }

    /**
     * Rekomendasi top seller berdasarkan sold_count + SUM(qty) dari transaction_items.
     */
    protected function topSeller(Request $request)
    {
        $limit = (int) $request->query(
            'limit',
            config('recommendations.top_seller_limit', 10)
        );

        $products = Product::query()
            // hitung total qty dari transaction_items
            ->withSum('transactionItems as items_qty_sum', 'qty')
            // urutkan berdasarkan kombinasi sold_count + qty dari transaction_items
            ->orderByDesc(DB::raw('sold_count + COALESCE(items_qty_sum, 0)'))
            ->limit($limit)
            ->get();

        return response()->json([
            'mode'   => 'top_seller',
            'data'   => $products,
            'limit'  => $limit,
        ]);
    }

    /**
     * Rekomendasi rule-based berdasarkan brand dari config/recommendations.php.
     *
     * Logika sederhana:
     * - Ambil brand dari produk yang diminta.
     * - Cek mapping brand di config('recommendations.brand_rules').
     * - Jika ada, pakai daftar brand mapping tsb.
     * - Jika tidak ada, fallback ke brand yang sama saja.
     */
    protected function ruleBasedByBrand(Request $request, string $productId)
    {
        $limit = (int) $request->query(
            'limit',
            config('recommendations.rule_based_limit', 10)
        );

        $product = Product::findOrFail($productId);

        // jika brand kosong, fallback ke top seller saja
        if (! $product->brand) {
            return $this->topSeller($request);
        }

        $rules = config('recommendations.brand_rules', []);

        // Contoh struktur brand_rules di config:
        // 'brand_rules' => [
        //     'Nike'  => ['Nike', 'Adidas'],
        //     'Adidas'=> ['Adidas', 'Puma'],
        // ];
        $brandsToUse = $rules[$product->brand] ?? [$product->brand];

        $query = Product::query()
            ->whereIn('brand', $brandsToUse)
            ->where('id', '!=', $product->id)
            ->withSum('transactionItems as items_qty_sum', 'qty')
            ->orderByDesc(DB::raw('sold_count + COALESCE(items_qty_sum, 0)'))
            ->limit($limit);

        $recommendations = $query->get();

        return response()->json([
            'mode'          => 'rule_based_brand',
            'base_product'  => $product,
            'based_on'      => [
                'brand'        => $product->brand,
                'brand_rules'  => $brandsToUse,
            ],
            'data'          => $recommendations,
            'limit'         => $limit,
        ]);
    }
}
