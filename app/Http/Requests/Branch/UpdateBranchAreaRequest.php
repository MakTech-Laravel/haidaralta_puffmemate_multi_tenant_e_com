<?php

namespace App\Http\Requests\Branch;

use App\Http\Requests\Concerns\ValidatesBranchArea;
use App\Models\Branch;
use App\Models\BranchArea;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchAreaRequest extends FormRequest
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
        /** @var BranchArea $area */
        $area = $this->route('area');

        return $this->branchAreaRules($branch->id, $area->id);
    }
}
