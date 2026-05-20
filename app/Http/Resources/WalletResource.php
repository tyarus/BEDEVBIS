<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'available_balance' => $this->available_balance,
            'held_amount_as_buyer' => $this->held_amount_as_buyer ?? 0,
            'held_amount_as_seller' => $this->held_amount_as_seller ?? 0,
            'total_topup' => $this->total_topup,
            'total_sales' => $this->total_sales,
            'total_withdraw' => $this->total_withdraw,
            'total_refund' => $this->total_refund,
        ];
    }
}
