<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0.01',
            'stock' => 'nullable|integer|min:0',
            'image_url' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'game_category' => ['sometimes', 'nullable', Rule::in(['mobile_legends', 'pubg_mobile', 'free_fire', 'efootball', 'fifa_26'])],
            'login_method' => ['sometimes', 'nullable', Rule::in(['facebook', 'google', 'x', 'konami_id', 'ea'])],
        ];
    }
}
