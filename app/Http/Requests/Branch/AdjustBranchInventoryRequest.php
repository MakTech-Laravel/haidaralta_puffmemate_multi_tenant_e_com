<?php

namespace App\Http\Requests\Branch;

use App\Http\Requests\Stock\AdjustStockRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustBranchInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->filled('variant_id')) {
            abort_unless((bool) config('catalog.show_product_variants'), 404);
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...AdjustStockRequest::typeQuantityReasonRules($this),
            'variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->whereNull('deleted_at')],
        ];
    }
}
