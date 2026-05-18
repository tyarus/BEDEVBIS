<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderTransactionMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sender_id' => $this->sender_id,
            'sender_name' => $this->sender->name ?? 'Unknown',
            'sender_role' => $this->sender_role,
            'message' => $this->message,
            'message_type' => $this->message_type,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
        ];
    }
}
