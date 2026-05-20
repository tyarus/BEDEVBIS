<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletWithdrawalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => 'wd-' . $this->created_at->format('Ymd') . '-' . str_pad($this->id, 4, '0', STR_PAD_LEFT),
            'reference_number' => $this->reference_number,
            'amount' => $this->amount,
            'bank_name' => $this->bank_name,
            'account_name' => $this->account_name,
            'account_number' => $this->account_number,
            'created_at' => $this->created_at,
        ];
    }
}
