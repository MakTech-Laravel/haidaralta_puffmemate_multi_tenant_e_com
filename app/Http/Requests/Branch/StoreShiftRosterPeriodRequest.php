<?php

namespace App\Http\Requests\Branch;

use App\Concerns\ValidatesWorkforceInput;
use App\Enums\ShiftOwnerType;
use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRosterPeriodRequest extends FormRequest
{
    use ValidatesWorkforceInput;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $branch = $this->route('branch');

        if ($branch instanceof Branch) {
            $this->merge([
                'owner_type' => ShiftOwnerType::Branch->value,
                'branch_id' => $branch->id,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->rosterPeriodRules();
    }
}
