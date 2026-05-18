<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderTransactionChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'account_match',
        'account_secured',
        'seller_device_removed',
        'completion_code_verified',
        'updated_by',
    ];

    protected $casts = [
        'account_match' => 'boolean',
        'account_secured' => 'boolean',
        'seller_device_removed' => 'boolean',
        'completion_code_verified' => 'boolean',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
