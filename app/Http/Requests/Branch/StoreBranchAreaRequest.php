<?php

namespace App\Http\Requests\Branch;

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
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $this->branchAreaRules($branch->id);
    }
}
