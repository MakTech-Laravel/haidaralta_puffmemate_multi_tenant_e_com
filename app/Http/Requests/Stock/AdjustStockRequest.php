<?php

namespace App\Http\Requests\Stock;

use App\Enums\InventoryTransactionType;
use Illuminate\Foundation\Http\FormRequest;
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
            'type' => ['required', Rule::in([
                InventoryTransactionType::StockIn->value,
                InventoryTransactionType::StockOut->value,
                InventoryTransactionType::Adjustment->value,
            ])],
            'quantity' => ['required', 'integer'],
        ];
    }
}
