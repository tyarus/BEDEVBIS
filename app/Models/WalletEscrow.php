<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletEscrow extends Model
{
    use HasFactory;

    protected $table = 'wallet_escrows';

    protected $fillable = [
        'order_id',
        'buyer_id',
        'seller_id',
        'amount',
        'status',
        'released_at',
        'refunded_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'released_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
