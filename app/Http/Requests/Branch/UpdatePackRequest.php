<?php

namespace App\Http\Requests\Branch;

use App\Http\Requests\Concerns\ValidatesCatalogProductImages;
use App\Http\Requests\Concerns\ValidatesPackFields;
use App\Models\Pack;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePackRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Pack $pack */
        $pack = $this->route('pack');

        return [
            ...$this->packFieldRules(),
            'remove_image' => ['sometimes', 'boolean'],
            'remove_gallery_ids' => ['nullable', 'array'],
            'remove_gallery_ids.*' => [
                'integer',
                Rule::exists('media', 'id')
                    ->where('model_type', $pack->getMorphClass())
                    ->where('model_id', $pack->id)
                    ->where('collection_name', 'gallery'),
            ],
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

                $pack = $this->route('pack');

                if (! $pack instanceof Pack) {
                    return;
                }

                $removeIds = collect($this->input('remove_gallery_ids', []))
                    ->map(fn ($id): int => (int) $id)
                    ->all();

                $existing = $pack->getMedia('gallery')
                    ->whereNotIn('id', $removeIds)
                    ->count();

                if ($existing + count($this->galleryFiles()) > 12) {
                    $validator->errors()->add('gallery', 'A pack can have at most 12 gallery images.');
                }
            },
        ];
    }
}
