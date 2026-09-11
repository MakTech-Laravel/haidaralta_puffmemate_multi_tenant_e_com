<?php

namespace App\Actions\Fortify;

use App\Enums\UserType;
use App\Models\User;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /**
     * Create a newly registered user from already-validated input.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        $email = $input['email'] ?? null;

        if (is_string($email) && trim($email) === '') {
            $email = null;
        }

        return User::create([
            'name' => $input['name'],
            'phone' => $input['phone'],
            'email' => $email,
            'password' => $input['password'],
            'user_type' => UserType::CUSTOMER,
            'branch_id' => null,
            'is_active' => true,
            'phone_verified_at' => null,
        ]);
    }
}
