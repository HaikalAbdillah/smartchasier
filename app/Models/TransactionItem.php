<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'transaction_id',
        'product_id',
        'qty',
        'price_each',
        'subtotal',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'qty' => 'integer',
        'price_each' => 'float',
        'subtotal' => 'float',
    ];

    /**
     * Get the parent transaction.
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the related product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
