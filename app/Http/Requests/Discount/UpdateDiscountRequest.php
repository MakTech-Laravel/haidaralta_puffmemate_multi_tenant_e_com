<?php

namespace App\Http\Requests\Discount;

use App\Enums\DiscountType;
use App\Models\BranchProductPrice;
use App\Services\Inventory\BranchPricingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateDiscountRequest extends FormRequest
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

        return [
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['integer', Rule::exists('branches', 'id')],
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
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

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $branchIds = collect($this->input('branch_ids', []))
                    ->map(fn ($id): int => (int) $id)
                    ->filter(fn (int $id): bool => $id > 0)
                    ->unique()
                    ->values()
                    ->all();
                $productIds = collect($this->input('product_ids', []))
                    ->map(fn ($id): int => (int) $id)
                    ->filter(fn (int $id): bool => $id > 0)
                    ->unique()
                    ->values()
                    ->all();

                $pricing = app(BranchPricingService::class);
                $prices = $pricing->pricesForPairs($branchIds, $productIds);
                $missing = 0;
                $fixedTooHigh = 0;
                $type = $this->string('discount_type')->toString();
                $discountValue = (float) $this->input('discount_price');

                foreach ($branchIds as $branchId) {
                    foreach ($productIds as $productId) {
                        $price = $prices->get($branchId.':'.$productId);

                        if (! $price instanceof BranchProductPrice) {
                            $missing++;

                            continue;
                        }

                        if (
                            $type === DiscountType::Fixed->value
                            && $discountValue > (float) $price->selling_price
                        ) {
                            $fixedTooHigh++;
                        }
                    }
                }

                if ($missing > 0) {
                    $validator->errors()->add(
                        'product_ids',
                        $missing === 1
                            ? '1 selected pair has no selling price. Set a price first.'
                            : "{$missing} selected pairs have no selling price. Set a price first.",
                    );
                }

                if ($fixedTooHigh > 0) {
                    $validator->errors()->add(
                        'discount_price',
                        'The fixed discount must be less than or equal to each selected selling price.',
                    );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('branch_id') && ! $this->filled('branch_ids')) {
            $this->merge([
                'branch_ids' => [(int) $this->input('branch_id')],
            ]);
        }

        if ($this->filled('product_id') && ! $this->filled('product_ids')) {
            $this->merge([
                'product_ids' => [(int) $this->input('product_id')],
            ]);
        }

        if (app()->bound('currentBranch') && ! $this->filled('branch_ids')) {
            $this->merge([
                'branch_ids' => [app('currentBranch')->id],
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
