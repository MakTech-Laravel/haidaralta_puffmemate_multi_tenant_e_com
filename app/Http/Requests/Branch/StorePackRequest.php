<?php

namespace App\Http\Requests\Branch;

use App\Http\Requests\Concerns\ValidatesCatalogProductImages;
use App\Http\Requests\Concerns\ValidatesPackFields;
use Illuminate\Foundation\Http\FormRequest;

class StorePackRequest extends FormRequest
{
    use ValidatesCatalogProductImages;
    use ValidatesPackFields;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->preparePackFields();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->packFieldRules(withItems: true);
    }
}
