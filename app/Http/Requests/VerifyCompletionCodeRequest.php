<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCompletionCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:9|regex:/^[A-Z0-9]{4}-?[A-Z0-9]{4}$/',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode penyelesaian harus diisi',
            'code.max' => 'Format kode tidak valid',
            'code.regex' => 'Kode penyelesaian harus format XXXX-XXXX atau XXXXXXXX',
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'code' => strtoupper($this->code),
        ]);
    }
}
