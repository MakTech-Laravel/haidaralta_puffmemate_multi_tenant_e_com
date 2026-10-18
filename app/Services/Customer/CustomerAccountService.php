<?php

namespace App\Services\Customer;

use App\Models\Address;
use App\Models\User;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CustomerAccountService
{
    public function __construct(private MediaUploadService $media) {}

    /**
     * @param  array{name: string, phone: string, email?: string|null, remove_avatar?: bool}  $data
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
     * @return array{name: string, mobile: string, address: string, city: string|null, postal_code: string|null, latitude: string|null, longitude: string|null}|null
     */
    public function deliveryAddress(User $user): ?array
    {
        $address = $user->savedAddress;

        if (! $address instanceof Address) {
            return null;
        }

        return [
            'name' => $address->receiver_name,
            'mobile' => $address->phone,
            'address' => $address->address,
            'city' => $address->city,
            'postal_code' => $address->postal_code,
            'latitude' => $address->latitude === null ? null : (string) $address->latitude,
            'longitude' => $address->longitude === null ? null : (string) $address->longitude,
        ];
    }

    /**
     * A customer keeps at most one saved delivery address; saving replaces it.
     *
     * @param  array{name: string, mobile: string, address: string, city?: string|null, postal_code?: string|null, latitude?: float|string|null, longitude?: float|string|null}  $data
     */
    public function saveDeliveryAddress(User $user, array $data): Address
    {
        return Address::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'type' => 'home',
                'receiver_name' => trim($data['name']),
                'phone' => $data['mobile'],
                'address' => $data['address'],
                'city' => $data['city'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ],
        );
    }

    public function removeDeliveryAddress(User $user): void
    {
        Address::query()->where('user_id', $user->id)->delete();
    }
}
