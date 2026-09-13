<?php

namespace App\Http\Requests\Platform;

use App\Http\Requests\Concerns\ValidatesCatalogColorCode;
use App\Http\Requests\Concerns\ValidatesCatalogProductImages;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
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
        /** @var Category $category */
        $category = $this->route('category');

        return [
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
                Rule::notIn([$category->id]),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('categories', 'slug')->ignore($category->id),
            ],
            'image' => $this->catalogImageFileRules(),
            'remove_image' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'boolean'],
            ...$this->colorCodeRules(),
        ];
    }
}
