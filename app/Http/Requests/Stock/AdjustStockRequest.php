<?php

namespace App\Http\Requests\Stock;

use App\Enums\InventoryAdjustmentReason;
use App\Enums\InventoryTransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdjustStockRequest extends FormRequest
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
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            ...self::typeQuantityReasonRules($this),
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('buying_price') === '') {
            $this->merge(['buying_price' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function typeQuantityReasonRules(?Request $request = null): array
    {
        $input = $request ?? request();
        $isAdjustment = $input->input('type') === InventoryTransactionType::Adjustment->value;
        $isStockIn = $input->input('type') === InventoryTransactionType::StockIn->value;
        $isOther = $isAdjustment
            && $input->input('reason') === InventoryAdjustmentReason::Other->value;

        return [
            'type' => ['required', Rule::enum(InventoryTransactionType::class)->only(
                InventoryTransactionType::manualCases(),
            )],
            'quantity' => [
                'required',
                'integer',
                $isAdjustment ? 'min:0' : 'min:1',
            ],
            'buying_price' => [
                Rule::requiredIf($isStockIn),
                Rule::prohibitedIf(! $isStockIn),
                'nullable',
                'numeric',
                'min:0',
            ],
            'reason' => [
                Rule::requiredIf($isAdjustment),
                Rule::prohibitedIf(! $isAdjustment),
                'nullable',
                Rule::enum(InventoryAdjustmentReason::class),
            ],
            'reason_note' => [
                Rule::requiredIf($isOther),
                Rule::prohibitedIf(! $isOther),
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
