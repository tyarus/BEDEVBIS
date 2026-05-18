<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'seller';
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0.01',
            'stock' => 'required|integer|min:0',
            'image_url' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'game_category' => ['nullable', Rule::in(['mobile_legends', 'pubg_mobile', 'free_fire', 'efootball', 'fifa_26'])],
            'login_method' => ['nullable', Rule::in(['facebook', 'google', 'x', 'konami_id', 'ea'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama produk harus diisi',
            'price.required' => 'Harga harus diisi',
            'price.min' => 'Harga harus lebih dari 0',
            'stock.required' => 'Stok harus diisi',
            'stock.min' => 'Stok tidak boleh negatif',
            'image_url.regex' => 'Format gambar tidak valid',
        ];
    }
}
