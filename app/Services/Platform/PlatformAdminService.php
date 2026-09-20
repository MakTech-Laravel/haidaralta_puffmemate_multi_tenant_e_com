<?php

namespace App\Services\Platform;

use App\Enums\UserType;
use App\Models\User;
use App\Support\PublicDiskMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PlatformAdminService
{
    /**
     * @param  array{name: string, phone: string, email?: string|null, password: string, roles?: list<string>, is_active?: bool}  $data
     */
    public function create(array $data, ?UploadedFile $avatar = null): User
    {
        return DB::transaction(function () use ($data, $avatar): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'user_type' => UserType::PLATFORM,
                'branch_id' => null,
                'is_active' => $data['is_active'] ?? true,
                'phone_verified_at' => now(),
                'avatar' => PublicDiskMedia::store($avatar, PublicDiskMedia::USER_AVATARS),
            ]);

            $user->syncRoles($data['roles'] ?? []);

            return $user;
        });
    }

    /**
     * @param  array{name: string, phone: string, email?: string|null, password?: string|null, roles: list<string>, is_active: bool, remove_avatar?: bool}  $data
     */
    public function update(User $user, array $data, ?UploadedFile $avatar = null): User
    {
        return DB::transaction(function () use ($user, $data, $avatar): User {
            $payload = [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'is_active' => $data['is_active'],
                'user_type' => UserType::PLATFORM,
                'branch_id' => null,
                'avatar' => PublicDiskMedia::replace(
                    $user->avatar,
                    $avatar,
                    PublicDiskMedia::USER_AVATARS,
                    (bool) ($data['remove_avatar'] ?? false),
                ),
            ];

            if (filled($data['password'] ?? null)) {
                $payload['password'] = $data['password'];
            }

            $user->update($payload);
            $user->unsetRelation('roles');
            $user->syncRoles($data['roles']);

            return $user->refresh();
        });
    }

    public function delete(User $user): void
    {
        PublicDiskMedia::delete($user->avatar);
        $user->delete();
    }
}
