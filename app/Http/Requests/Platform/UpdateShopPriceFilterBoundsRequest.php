<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateShopPriceFilterBoundsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'slider_min' => ['required', 'numeric', 'min:0'],
            'slider_max' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $min = $this->input('slider_min');
            $max = $this->input('slider_max');

            if ($min === null || $max === null) {
                return;
            }

            if ((float) $min >= (float) $max) {
                $validator->errors()->add(
                    'slider_max',
                    'Maximum must be greater than minimum.',
                );
            }
        });
    }
}
