<?php

namespace App\Services\Platform;

use App\Enums\CustomerAddressType;
use App\Enums\CustomerRegistrationStatus;
use App\Enums\UserType;
use App\Models\CustomerAddress;
use App\Models\User;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PlatformCustomerService
{
    public function __construct(private MediaUploadService $media) {}

    /**
     * @param  array{
     *     name: string,
     *     phone: string,
     *     email?: string|null,
     *     password: string,
     *     is_active?: bool,
     *     shipping?: array{address?: string|null, city?: string|null, region?: string|null, zip?: string|null}|null,
     *     billing?: array{name?: string|null, address?: string|null, city?: string|null, region?: string|null, zip?: string|null, email?: string|null, phone?: string|null}|null
     * }  $data
     */
    public function create(array $data, ?UploadedFile $avatar = null): User
    {
        return DB::transaction(function () use ($data, $avatar): User {
            $email = $data['email'] ?? null;

            $user = User::query()->create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $email,
                'password' => $data['password'],
                'user_type' => UserType::CUSTOMER,
                'registration_status' => CustomerRegistrationStatus::Registered,
                'branch_id' => null,
                'is_active' => $data['is_active'] ?? true,
                'phone_verified_at' => now(),
                'email_verified_at' => filled($email) ? now() : null,
            ]);

            $this->media->syncSingle($user, 'avatar', $avatar);
            $this->syncShippingAddress($user, $data['shipping'] ?? null);
            $this->syncBillingAddress($user, $data['billing'] ?? null);

            return $user->refresh();
        });
    }

    /**
     * @param  array{
     *     name: string,
     *     phone: string,
     *     email?: string|null,
     *     password?: string|null,
     *     is_active: bool,
     *     remove_avatar?: bool,
     *     shipping?: array{address?: string|null, city?: string|null, region?: string|null, zip?: string|null}|null,
     *     billing?: array{name?: string|null, address?: string|null, city?: string|null, region?: string|null, zip?: string|null, email?: string|null, phone?: string|null}|null
     * }  $data
     */
    public function update(User $user, array $data, ?UploadedFile $avatar = null): User
    {
        return DB::transaction(function () use ($user, $data, $avatar): User {
            $email = $data['email'] ?? null;
            $previousEmail = $user->email;

            $payload = [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $email,
                'is_active' => $data['is_active'],
                'user_type' => UserType::CUSTOMER,
                'branch_id' => null,
            ];

            if (filled($data['password'] ?? null)) {
                $payload['password'] = $data['password'];
            }

            if (filled($email) && $email !== $previousEmail) {
                $payload['email_verified_at'] = now();
            }

            if (! filled($email)) {
                $payload['email_verified_at'] = null;
            }

            $user->update($payload);

            $this->media->syncSingle(
                $user,
                'avatar',
                $avatar,
                (bool) ($data['remove_avatar'] ?? false),
            );
            $this->syncShippingAddress($user, $data['shipping'] ?? null);
            $this->syncBillingAddress($user, $data['billing'] ?? null);

            return $user->refresh();
        });
    }

    public function setActive(User $user, bool $isActive): User
    {
        $user->update(['is_active' => $isActive]);

        return $user->refresh();
    }

    /**
     * @param  array{address?: string|null, city?: string|null, region?: string|null, zip?: string|null}|null  $shipping
     */
    private function syncShippingAddress(User $user, ?array $shipping): void
    {
        if ($shipping === null || ! $this->addressHasValues($shipping)) {
            return;
        }

        CustomerAddress::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'type' => CustomerAddressType::Shipping,
            ],
            [
                'address' => $shipping['address'] ?? null,
                'city' => $shipping['city'] ?? null,
                'region' => $shipping['region'] ?? null,
                'zip' => $shipping['zip'] ?? null,
            ],
        );
    }

    /**
     * @param  array{name?: string|null, address?: string|null, city?: string|null, region?: string|null, zip?: string|null, email?: string|null, phone?: string|null}|null  $billing
     */
    private function syncBillingAddress(User $user, ?array $billing): void
    {
        if ($billing === null || ! $this->addressHasValues($billing)) {
            return;
        }

        CustomerAddress::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'type' => CustomerAddressType::Billing,
            ],
            [
                'name' => $billing['name'] ?? null,
                'address' => $billing['address'] ?? null,
                'city' => $billing['city'] ?? null,
                'region' => $billing['region'] ?? null,
                'zip' => $billing['zip'] ?? null,
                'email' => $billing['email'] ?? null,
                'phone' => $billing['phone'] ?? null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $address
     */
    private function addressHasValues(array $address): bool
    {
        foreach ($address as $value) {
            if (filled($value)) {
                return true;
            }
        }

        return false;
    }
}
