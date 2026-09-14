<?php

namespace App\Http\Requests\Platform;

use App\Http\Requests\Concerns\ValidatesCatalogColorCode;
use App\Http\Requests\Concerns\ValidatesCatalogProductImages;
use App\Http\Requests\Concerns\ValidatesCategoryLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCategoryRequest extends FormRequest
{
    use ValidatesCatalogColorCode;
    use ValidatesCatalogProductImages;
    use ValidatesCategoryLimits;

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
            'slogan' => ['nullable', 'string', 'max:120'],
            'image' => $this->catalogImageFileRules(),
            'status' => ['sometimes', 'boolean'],
            'featured' => ['sometimes', 'boolean'],
            'show_in_nav' => ['sometimes', 'boolean'],
            'nav_sort' => ['nullable', 'integer', 'min:0', 'max:65535'],
            ...$this->colorCodeRules(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateCategoryLimits($validator);
    }
}
