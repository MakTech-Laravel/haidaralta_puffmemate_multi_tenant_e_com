<?php

namespace App\Http\Requests\Branch;

use App\Enums\DriverAssignmentStatus;
use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'driver_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('user_type', UserType::DRIVER->value),
            ],
            'status' => ['nullable', Rule::enum(DriverAssignmentStatus::class)],
        ];
    }
}
