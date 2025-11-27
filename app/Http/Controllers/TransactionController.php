<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * GET /api/transactions
     * Tampilkan daftar transaksi lengkap dengan item dan produk.
     */
    public function index()
    {
        $transactions = Transaction::with(['transactionItems.product'])
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => $transactions,
        ]);
    }

    /**
     * POST /api/checkout
     * Membuat transaksi baru (checkout) beserta item dan update stok produk.
     *
     * Contoh payload:
     * {
     *   "customer_name": "John Doe",
     *   "payment_method": "cash",
     *   "items": [
     *     { "product_id": 1, "qty": 2 },
     *     { "product_id": 3, "qty": 1 }
     *   ]
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name'           => 'required|string|max:255',
            'payment_method'          => 'required|string|max:100',
            'items'                   => 'required|array|min:1',
            'items.*.product_id'      => 'required|integer|exists:products,id',
            'items.*.qty'             => 'required|integer|min:1',
        ]);

        $transaction = DB::transaction(function () use ($validated) {
            $items = $validated['items'];

            $preparedItems = [];
            $total = 0;

            // Lock stok produk dan hitung total
            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                $qty = $item['qty'];

                if ($product->stock < $qty) {
                    abort(422, sprintf(
                        'Stock not enough for product ID %d. Available: %d, requested: %d',
                        $product->id,
                        $product->stock,
                        $qty
                    ));
                }

                $priceEach = (float) $product->price;
                $subtotal = $priceEach * $qty;
                $total += $subtotal;

                $preparedItems[] = [
                    'product'    => $product,
                    'qty'        => $qty,
                    'price_each' => $priceEach,
                    'subtotal'   => $subtotal,
                ];
            }

            // Buat transaksi utama
            $transaction = Transaction::create([
                'customer_name'  => $validated['customer_name'],
                'payment_method' => $validated['payment_method'],
                'total_amount'   => $total,
            ]);

            // Buat item transaksi dan update stok + sold_count
            foreach ($preparedItems as $row) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id'     => $row['product']->id,
                    'qty'            => $row['qty'],
                    'price_each'     => $row['price_each'],
                    'subtotal'       => $row['subtotal'],
                ]);

                $row['product']->decrement('stock', $row['qty']);
                $row['product']->increment('sold_count', $row['qty']);
            }

            return $transaction->load(['transactionItems.product']);
        });

        return response()->json([
            'message' => 'Checkout successfully.',
            'data'    => $transaction,
        ], 201);
    }

    /**
     * Optional: GET /api/transactions/{id}
     * Tampilkan detail satu transaksi.
     */
    public function show(string $id)
    {
        $transaction = Transaction::with(['transactionItems.product'])
            ->findOrFail($id);

        return response()->json([
            'data' => $transaction,
        ]);
    }

    /**
     * Tidak digunakan saat ini.
     */
    public function update(Request $request, string $id)
    {
        abort(405, 'Updating transactions is not supported.');
    }

    /**
     * Tidak digunakan saat ini.
     */
    public function destroy(string $id)
    {
        abort(405, 'Deleting transactions is not supported.');
    }
}
