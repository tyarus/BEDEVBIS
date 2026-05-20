<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'seller';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|integer|min:1',
            'bank_name' => 'required|string|max:50',
            'account_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:30',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Jumlah withdraw wajib diisi',
            'amount.integer' => 'Jumlah withdraw harus berupa angka',
            'amount.min' => 'Jumlah withdraw minimal 1',
            'bank_name.required' => 'Nama bank wajib diisi',
            'bank_name.max' => 'Nama bank maksimal 50 karakter',
            'account_name.required' => 'Nama pemilik rekening wajib diisi',
            'account_name.max' => 'Nama pemilik rekening maksimal 100 karakter',
            'account_number.required' => 'Nomor rekening wajib diisi',
            'account_number.max' => 'Nomor rekening maksimal 30 karakter',
        ];
    }
}
