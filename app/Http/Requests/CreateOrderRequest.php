<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cafe_id' => ['required', 'integer', 'exists:cafes,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.option_ids' => ['nullable', 'array'],
            'items.*.option_ids.*' => ['integer', 'exists:product_options,id'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $cafeId = $this->input('cafe_id');
            $items = $this->input('items', []);

            foreach ($items as $index => $item) {
                if (! isset($item['product_id'])) {
                    continue;
                }

                $product = Product::find($item['product_id']);

                if ($product && (int) $product->cafe_id !== (int) $cafeId) {
                    $validator->errors()->add(
                        "items.{$index}.product_id",
                        'Produk tidak dijual di cafe ini.'
                    );
                }
            }
        });
    }
}
