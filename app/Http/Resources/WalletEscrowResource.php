<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletEscrowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'order_id' => $this->order_id,
            'buyer_id' => $this->buyer_id,
            'seller_id' => $this->seller_id,
            'amount' => $this->amount,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'released_at' => $this->released_at,
            'refunded_at' => $this->refunded_at,
        ];
    }
}
