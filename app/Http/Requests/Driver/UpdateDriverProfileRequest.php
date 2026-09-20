<?php

namespace App\Http\Requests\Driver;

use App\Concerns\NormalizesPhoneInput;
use App\Enums\DriverVehicleType;
use App\Enums\UserType;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDriverProfileRequest extends FormRequest
{
    use NormalizesPhoneInput;

    public function authorize(): bool
    {
        return $this->user()?->user_type === UserType::DRIVER;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeNormalizedPhone();
        $this->normalizeBlankEmailToNull();

        if ($this->input('license_number') === '') {
            $this->merge(['license_number' => null]);
        }

        if ($this->input('address') === '') {
            $this->merge(['address' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $driver */
        $driver = $this->user();
        $driver->loadMissing('driverProfile');

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', ...Phone::validationRule(), Rule::unique('users', 'phone')->ignore($driver->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($driver->id)],
            'license_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('driver_profiles', 'license_number')->ignore($driver->driverProfile?->id),
            ],
            'vehicle_type' => ['required', Rule::enum(DriverVehicleType::class)],
            'address' => ['nullable', 'string', 'max:2000'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'vehicle_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['sometimes', 'boolean'],
            'remove_vehicle_image' => ['sometimes', 'boolean'],
        ];
    }
}
