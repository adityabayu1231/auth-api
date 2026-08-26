<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership/role check dilakukan via ProductPolicy di controller
    }

    public function rules(): array
    {
        return [
            'category' => ['sometimes', 'string', Rule::in(['coffee', 'non-coffee', 'snack'])],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'base_price' => ['sometimes', 'integer', 'min:0'],
            'image_path' => ['nullable', 'string'],
            'service_time_minutes' => ['nullable', 'integer', 'min:0'],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }
}
