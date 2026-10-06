<?php

namespace App\Http\Requests\Platform;

use App\Http\Requests\Concerns\ValidatesBranchArea;
use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;

class StoreBranchAreaRequest extends FormRequest
{
    use ValidatesBranchArea;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareBranchArea();
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Branch|null $branch */
        $branch = $this->route('branch');

        return $this->branchAreaRules($branch?->id);
    }
}
