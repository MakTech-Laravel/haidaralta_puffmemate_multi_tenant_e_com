<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_unless((bool) config('catalog.show_bulk_import'), 404);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['integer', Rule::exists('branches', 'id')],
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'quantity' => ['required', 'integer', 'min:1'],
            'buying_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (app()->bound('currentBranch') && ! $this->filled('branch_ids')) {
            $this->merge([
                'branch_ids' => [app('currentBranch')->id],
            ]);
        }

        if ($this->input('buying_price') === '') {
            $this->merge(['buying_price' => null]);
        }
    }
}
