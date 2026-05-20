<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletLedgerEntry extends Model
{
    use HasFactory;

    protected $table = 'wallet_ledger_entries';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'order_id',
        'type',
        'direction',
        'amount',
        'balance_after',
        'description',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
