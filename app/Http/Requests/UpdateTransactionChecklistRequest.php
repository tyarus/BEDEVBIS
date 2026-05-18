<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_match' => 'sometimes|boolean',
            'account_secured' => 'sometimes|boolean',
            'seller_device_removed' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'account_match.boolean' => 'Nilai account_match harus boolean',
            'account_secured.boolean' => 'Nilai account_secured harus boolean',
            'seller_device_removed.boolean' => 'Nilai seller_device_removed harus boolean',
        ];
    }
}
