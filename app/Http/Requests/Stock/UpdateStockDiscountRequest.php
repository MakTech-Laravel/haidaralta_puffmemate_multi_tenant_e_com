<?php

namespace App\Http\Requests\Stock;

use App\Enums\DiscountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStockDiscountRequest extends FormRequest
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
        $type = $this->string('discount_type')->toString();
        $hasDiscount = in_array($type, [
            DiscountType::Fixed->value,
            DiscountType::Percent->value,
        ], true);

        $discountPriceRules = $hasDiscount
            ? ['required', 'numeric', 'min:0']
            : ['nullable', 'prohibited'];

        if ($type === DiscountType::Percent->value) {
            $discountPriceRules[] = 'max:100';
        }

        if ($type === DiscountType::Fixed->value) {
            $discountPriceRules[] = 'lte:selling_price';
        }

        return [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['required', Rule::enum(DiscountType::class)],
            'discount_price' => $discountPriceRules,
            'start_date' => $hasDiscount
                ? ['nullable', 'date']
                : ['nullable', 'prohibited'],
            'end_date' => $hasDiscount
                ? ['nullable', 'date', 'after_or_equal:start_date']
                : ['nullable', 'prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('discount_type')) {
            $this->merge([
                'discount_type' => $this->filled('discount_price')
                    ? DiscountType::Fixed->value
                    : DiscountType::None->value,
            ]);
        }

        if ($this->input('discount_type') === DiscountType::None->value) {
            $this->merge([
                'discount_price' => null,
                'start_date' => null,
                'end_date' => null,
            ]);
        }
    }
}
