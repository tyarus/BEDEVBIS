<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderTransactionChat extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'status',
        'completion_code_hash',
        'completion_code_expires_at',
        'completion_code_verified_at',
    ];

    protected $casts = [
        'completion_code_expires_at' => 'datetime',
        'completion_code_verified_at' => 'datetime',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function checklist()
    {
        return $this->hasOne(OrderTransactionChecklist::class, 'order_id', 'order_id');
    }

    public function messages()
    {
        return $this->hasMany(OrderTransactionMessage::class, 'order_id', 'order_id');
    }

    public function activities()
    {
        return $this->hasMany(OrderTransactionActivity::class, 'order_id', 'order_id');
    }
}
