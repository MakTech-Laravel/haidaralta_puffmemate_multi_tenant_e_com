<?php

namespace App\Http\Requests\Storefront;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreWholesaleInquiryRequest extends FormRequest
{
    public const MAX_ITEMS = 50;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:2000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'message' => ['nullable', 'required_without:items', 'string', 'max:5000'],
            'items' => ['nullable', 'array', 'max:'.self::MAX_ITEMS],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('status', true),
            ],
            'items.*.variant_id' => [
                'nullable',
                'integer',
                Rule::exists('product_variants', 'id')->where('status', true)->whereNull('deleted_at'),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required_without' => 'Add at least one product or write a message.',
            'items.*.product_id.exists' => 'This product is no longer available.',
            'items.*.variant_id.exists' => 'This option is no longer available.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var list<array{product_id: int|string, variant_id?: int|string|null, quantity: int|string}> $items */
                $items = $this->input('items', []) ?: [];

                if ($items === []) {
                    return;
                }

                $minimums = Product::query()
                    ->whereIn('id', array_map(fn (array $item): int => (int) $item['product_id'], $items))
                    ->pluck('min_wholesale_qty', 'id');
                $variantOwners = ProductVariant::query()
                    ->whereIn('id', array_filter(array_map(fn (array $item): ?int => self::variantId($item), $items)))
                    ->pluck('product_id', 'id');
                $seen = [];

                foreach ($items as $index => $item) {
                    $variantId = self::variantId($item);

                    if ($variantId !== null && (int) $variantOwners->get($variantId) !== (int) $item['product_id']) {
                        $validator->errors()->add("items.{$index}.variant_id", 'This option does not belong to the product.');

                        continue;
                    }

                    $line = $item['product_id'].':'.($variantId ?? '');

                    if (isset($seen[$line])) {
                        $validator->errors()->add("items.{$index}.product_id", 'This product is already in your list.');

                        continue;
                    }

                    $seen[$line] = true;
                    $minimum = $minimums->get((int) $item['product_id']);

                    if ($minimum !== null && (int) $item['quantity'] < (int) $minimum) {
                        $validator->errors()->add(
                            "items.{$index}.quantity",
                            "Minimum wholesale quantity is {$minimum}.",
                        );
                    }
                }
            },
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private static function variantId(array $item): ?int
    {
        $value = $item['variant_id'] ?? null;

        return $value === null || $value === '' ? null : (int) $value;
    }
}
