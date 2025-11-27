<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'customer_name',
        'payment_method',
        'total_amount',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'total_amount' => 'float',
    ];

    /**
     * Get the transaction items for this transaction.
     */
    public function transactionItems()
    {
        return $this->hasMany(TransactionItem::class);
    }
}
