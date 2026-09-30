<?php

namespace App\Http\Requests\Branch;

use App\Enums\DriverStockAssignmentMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignDriverProductsRequest extends FormRequest
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
            'mode' => ['required', Rule::enum(DriverStockAssignmentMode::class)],
            'driver_ids' => ['required', 'array', 'min:1'],
            'driver_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $mode = DriverStockAssignmentMode::tryFrom((string) $this->input('mode'));
            $driverIds = $this->input('driver_ids', []);

            if ($mode === DriverStockAssignmentMode::Single && count($driverIds) !== 1) {
                $validator->errors()->add('driver_ids', 'Single mode requires exactly one driver.');
            }
        });
    }
}
