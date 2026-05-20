<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relationships
    public function products()
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function buyerOrders()
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function sellerOrders()
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    public function escrowLogs()
    {
        return $this->hasMany(EscrowLog::class, 'actor_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function walletAccount()
    {
        return $this->hasOne(WalletAccount::class);
    }

    public function walletLedgerEntries()
    {
        return $this->hasMany(WalletLedgerEntry::class);
    }

    public function buyerEscrows()
    {
        return $this->hasMany(WalletEscrow::class, 'buyer_id');
    }

    public function sellerEscrows()
    {
        return $this->hasMany(WalletEscrow::class, 'seller_id');
    }

    public function withdrawals()
    {
        return $this->hasMany(WalletWithdrawal::class, 'seller_id');
    }
}
