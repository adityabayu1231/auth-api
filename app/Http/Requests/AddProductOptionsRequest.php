<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AddProductOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership/role check dilakukan via ProductPolicy di controller
    }

    public function rules(): array
    {
        return [
            'options' => ['required', 'array', 'min:1'],
            'options.*.option_type' => ['required', 'string', Rule::in(['size', 'sweetness', 'ice', 'milk'])],
            'options.*.option_value' => ['required', 'string', 'max:255'],
            'options.*.extra_price' => ['required', 'integer', 'min:0'],
            'options.*.is_default' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $options = $this->input('options', []);
            $seenDefaultFor = [];

            foreach ($options as $index => $option) {
                if (empty($option['is_default'])) {
                    continue;
                }

                $type = $option['option_type'] ?? null;

                if (isset($seenDefaultFor[$type])) {
                    $validator->errors()->add(
                        "options.{$index}.is_default",
                        "Hanya boleh ada 1 opsi default untuk option_type '{$type}'."
                    );
                }

                $seenDefaultFor[$type] = true;
            }
        });
    }
}
