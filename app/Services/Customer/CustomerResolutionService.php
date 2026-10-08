<?php

namespace App\Services\Customer;

use App\Enums\CustomerRegistrationStatus;
use App\Enums\UserType;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerResolutionService
{
    public function findByPhone(string $phone): ?User
    {
        return User::query()
            ->where('phone', Phone::toE164($phone))
            ->where('user_type', UserType::CUSTOMER)
            ->first();
    }

    /**
     * Soft search customers by name, email, or phone for Place Order lookup.
     *
     * @return Collection<int, User>
     */
    public function search(string $query, int $limit = 8): Collection
    {
        $term = trim($query);

        if ($term === '' || $limit < 1) {
            return new Collection;
        }

        $like = '%'.$term.'%';

        return User::query()
            ->where('user_type', UserType::CUSTOMER)
            ->where('is_active', true)
            ->where(function ($builder) use ($like, $term): void {
                $builder
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);

                Phone::constrainLike($builder, 'phone', $term);
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'phone', 'email', 'registration_status']);
    }

    /**
     * Reuse a registered or provisional customer, or create one provisional identity.
     *
     * @throws ValidationException
     */
    public function resolve(?int $customerId, ?string $phone, ?string $name): User
    {
        if ($customerId !== null) {
            $customer = User::query()
                ->whereKey($customerId)
                ->where('user_type', UserType::CUSTOMER)
                ->lockForUpdate()
                ->first();

            if ($customer === null) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Choose a customer before placing the order.',
                ]);
            }

            return $customer;
        }

        if (! is_string($phone) || $phone === '') {
            throw ValidationException::withMessages([
                'phone' => 'Enter the customer phone number.',
            ]);
        }

        $e164 = Phone::toE164($phone);

        $existing = User::query()
            ->where('phone', $e164)
            ->lockForUpdate()
            ->first();

        if ($existing !== null) {
            if ($existing->user_type !== UserType::CUSTOMER) {
                throw ValidationException::withMessages([
                    'phone' => 'That phone number cannot be used for a customer sale.',
                ]);
            }

            return $existing;
        }

        $trimmedName = trim((string) $name);

        if ($trimmedName === '') {
            throw ValidationException::withMessages([
                'name' => 'Enter the customer name.',
            ]);
        }

        return $this->createProvisional($e164, $trimmedName);
    }

    public function createProvisional(string $phone, string $name): User
    {
        try {
            return DB::transaction(function () use ($phone, $name): User {
                return User::query()->create([
                    'name' => $name,
                    'phone' => $phone,
                    'email' => null,
                    'password' => null,
                    'user_type' => UserType::CUSTOMER,
                    'registration_status' => CustomerRegistrationStatus::Provisional,
                    'branch_id' => null,
                    'is_active' => true,
                    'phone_verified_at' => null,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            $existing = User::query()->where('phone', $phone)->lockForUpdate()->first();

            if ($existing === null || $existing->user_type !== UserType::CUSTOMER) {
                throw ValidationException::withMessages([
                    'phone' => 'That phone number cannot be used for a customer sale.',
                ]);
            }

            return $existing;
        }
    }
}
