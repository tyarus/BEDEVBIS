<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTransactionMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|max:1000',
            'message_type' => 'required|in:text,system,checklist_update,status_update,completion_code',
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Pesan harus diisi',
            'message.max' => 'Pesan maksimal 1000 karakter',
            'message_type.required' => 'Tipe pesan harus diisi',
            'message_type.in' => 'Tipe pesan tidak valid',
        ];
    }
}
