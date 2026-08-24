<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOperatingHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership check dilakukan via CafePolicy di controller
    }

    public function rules(): array
    {
        return [
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'hours.*.open_time' => ['nullable', 'date_format:H:i'],
            'hours.*.close_time' => ['nullable', 'date_format:H:i'],
            'hours.*.is_closed' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'hours.*.close_time.after' => 'Jam tutup harus setelah jam buka.',
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator)
    {
        $validator->after(function ($validator) {
            $hours = $this->input('hours', []);
            foreach ($hours as $index => $hour) {
                $isClosed = $hour['is_closed'] ?? false;
                if ($isClosed) {
                    continue;
                }
                $open = $hour['open_time'] ?? null;
                $close = $hour['close_time'] ?? null;
                if ($open && $close && $close <= $open) {
                    $validator->errors()->add(
                        "hours.{$index}.close_time",
                        'Jam tutup harus setelah jam buka.'
                    );
                }
            }
        });
    }
}
