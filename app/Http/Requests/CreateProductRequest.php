<?php

namespace App\Http\Requests;

use App\Policies\ProductPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(ProductPolicy::class)->create(
            $this->user(),
            (int) $this->input('cafe_id')
        );
    }

    public function rules(): array
    {
        return [
            'cafe_id' => ['required', 'integer', 'exists:cafes,id'],
            'category' => ['required', 'string', Rule::in(['coffee', 'non-coffee', 'snack'])],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'base_price' => ['required', 'integer', 'min:0'],
            'image_path' => ['nullable', 'string'],
            'service_time_minutes' => ['nullable', 'integer', 'min:0'],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }
}
