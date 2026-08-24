<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadCafePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership check dilakukan via CafePolicy di controller
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'], // max 2MB
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
