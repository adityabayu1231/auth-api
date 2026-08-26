<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership/role check dilakukan via ProductPolicy di controller
    }

    public function rules(): array
    {
        return [
            'option_type' => ['sometimes', 'string', Rule::in(['size', 'sweetness', 'ice', 'milk'])],
            'option_value' => ['sometimes', 'string', 'max:255'],
            'extra_price' => ['sometimes', 'integer', 'min:0'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
