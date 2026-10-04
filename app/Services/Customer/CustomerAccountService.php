<?php

namespace App\Services\Customer;

use App\Enums\CustomerAddressType;
use App\Models\CustomerAddress;
use App\Models\User;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CustomerAccountService
{
    public function __construct(private MediaUploadService $media) {}

    /**
     * @param  array{name: string, phone: string, email?: string|null, region?: string|null, city?: string|null, zip?: string|null, address?: string|null, remove_avatar?: bool}  $data
     */
    public function updateProfile(User $user, array $data, ?UploadedFile $avatar = null): User
    {
        return DB::transaction(function () use ($user, $data, $avatar): User {
            $user->fill([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
            ]);

            if ($user->isDirty('phone')) {
                $user->phone_verified_at = null;
            }

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            $this->media->syncSingle(
                $user,
                'avatar',
                $avatar,
                (bool) ($data['remove_avatar'] ?? false),
            );

            CustomerAddress::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => CustomerAddressType::Shipping,
                ],
                [
                    'region' => $data['region'] ?? null,
                    'city' => $data['city'] ?? null,
                    'zip' => $data['zip'] ?? null,
                    'address' => $data['address'] ?? null,
                ],
            );

            return $user->refresh();
        });
    }

    public function updatePassword(User $user, string $password): void
    {
        $user->update([
            'password' => $password,
        ]);
    }

    /**
     * @param  array{name: string, address: string, region: string, city: string, zip: string, email?: string|null, phone: string}  $data
     */
    public function updateBillingAddress(User $user, array $data): CustomerAddress
    {
        return CustomerAddress::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'type' => CustomerAddressType::Billing,
            ],
            [
                'name' => $data['name'],
                'address' => $data['address'],
                'region' => $data['region'],
                'city' => $data['city'],
                'zip' => $data['zip'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'],
            ],
        );
    }
}
