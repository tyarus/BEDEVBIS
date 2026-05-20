<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletAccount extends Model
{
    use HasFactory;

    protected $table = 'wallet_accounts';

    protected $fillable = [
        'user_id',
        'available_balance',
        'total_topup',
        'total_sales',
        'total_withdraw',
        'total_refund',
    ];

    protected $casts = [
        'available_balance' => 'integer',
        'total_topup' => 'integer',
        'total_sales' => 'integer',
        'total_withdraw' => 'integer',
        'total_refund' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function escrows()
    {
        return $this->hasMany(WalletEscrow::class, 'seller_id', 'user_id');
    }

    public function ledgerEntries()
    {
        return $this->hasMany(WalletLedgerEntry::class, 'user_id');
    }

    public function withdrawals()
    {
        return $this->hasMany(WalletWithdrawal::class, 'seller_id', 'user_id');
    }
}
