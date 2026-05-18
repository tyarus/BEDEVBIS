<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderTransactionChatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_id' => $this->order_id,
            'status' => $this->status,
            'checklist' => [
                'account_match' => $this->checklist->account_match ?? false,
                'account_secured' => $this->checklist->account_secured ?? false,
                'seller_device_removed' => $this->checklist->seller_device_removed ?? false,
                'completion_code_verified' => $this->checklist->completion_code_verified ?? false,
            ],
            'completion_code' => $this->getCompletionCode(),
            'completion_code_expires_at' => $this->completion_code_expires_at,
            'completion_code_verified_at' => $this->completion_code_verified_at,
            'messages' => OrderTransactionMessageResource::collection($this->messages),
            'activities' => OrderTransactionActivityResource::collection($this->activities),
            'updated_at' => $this->updated_at,
        ];
    }

    private function getCompletionCode()
    {
        // Jika code sudah diverifikasi, tampil MASKED untuk security
        if ($this->completion_code_verified_at) {
            return 'MASKED';
        }

        // Jika code belum diverifikasi tapi ada hash, coba ambil dari cache
        if ($this->completion_code_hash) {
            $cached = \Illuminate\Support\Facades\Cache::get("completion_code_{$this->order_id}");
            return $cached ? $cached : 'LIHAT_RESPONSE_GENERATE';
        }

        return null;
    }
}
