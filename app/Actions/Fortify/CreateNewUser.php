<?php

namespace App\Actions\Fortify;

use App\Enums\CustomerRegistrationStatus;
use App\Enums\UserType;
use App\Models\User;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /**
     * Create a newly registered customer from already-validated input.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        return User::create([
            'name' => (string) $input['name'],
            'phone' => (string) $input['phone'],
            'email' => null,
            'password' => $input['password'],
            'user_type' => UserType::CUSTOMER,
            'registration_status' => CustomerRegistrationStatus::Registered,
            'branch_id' => null,
            'is_active' => true,
            'phone_verified_at' => null,
        ]);
    }
}
