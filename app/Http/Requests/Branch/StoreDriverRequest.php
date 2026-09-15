<?php

namespace App\Http\Requests\Branch;

use App\Concerns\NormalizesPhoneInput;
use App\Concerns\ValidatesDriverRosterPeriod;
use App\Enums\DriverAvailabilityStatus;
use App\Enums\DriverVehicleType;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreDriverRequest extends FormRequest
{
    use NormalizesPhoneInput;
    use ValidatesDriverRosterPeriod;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeNormalizedPhone();
        $this->normalizeBlankEmailToNull();
        $this->stampNestedRosterBranchId();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', ...Phone::validationRule(), Rule::unique('users', 'phone')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'license_number' => ['nullable', 'string', 'max:100', Rule::unique('driver_profiles', 'license_number')],
            'vehicle_type' => ['required', Rule::enum(DriverVehicleType::class)],
            'availability_status' => ['nullable', Rule::enum(DriverAvailabilityStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:2000'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'vehicle_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
            ...$this->nestedRosterRules(),
        ];
    }
}
