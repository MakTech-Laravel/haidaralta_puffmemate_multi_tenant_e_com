<?php

namespace App\Http\Requests\Branch;

use App\Http\Requests\Concerns\ValidatesCatalogColorCode;
use App\Http\Requests\Concerns\ValidatesCatalogProductImages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    use ValidatesCatalogColorCode;
    use ValidatesCatalogProductImages;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->decodeColorCodeInput();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('categories', 'slug')],
            'image' => $this->catalogImageFileRules(),
            'status' => ['sometimes', 'boolean'],
            'featured' => ['sometimes', 'boolean'],
            ...$this->colorCodeRules(),
        ];
    }
}
