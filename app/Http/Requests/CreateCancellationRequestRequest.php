<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCancellationRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'in:urgent_payment_delay,product_mismatch,ordering_mistake,other',
            ],
            'details' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan pembatalan harus dipilih',
            'reason.in' => 'Alasan pembatalan tidak valid',
            'details.max' => 'Penjelasan detail maksimal 500 karakter',
        ];
    }
}
