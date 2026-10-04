<?php

namespace App\Services\Branch;

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Exceptions\BranchAlreadyHasSuperAdminException;
use App\Models\Branch;
use App\Models\User;
use App\Services\Media\MediaUploadService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class BranchService
{
    public function __construct(private MediaUploadService $media) {}

    public function create(array $data, ?UploadedFile $logo = null): Branch
    {
        return DB::transaction(function () use ($data, $logo): Branch {
            return $this->createBranchRecord($data, $logo);
        });
    }

    /**
     * Create a branch with its first super-admin in a single transaction.
     *
     * @param  array{name: string, phone: string, email?: string|null, password: string}  $adminData
     */
    public function createWithSuperAdmin(array $data, array $adminData, ?UploadedFile $logo = null): Branch
    {
        return DB::transaction(function () use ($data, $adminData, $logo): Branch {
            $branch = $this->createBranchRecord($data, $logo);

            $this->createSuperAdmin($branch, $adminData);

            return $branch;
        });
    }

    private function createBranchRecord(array $data, ?UploadedFile $logo = null): Branch
    {
        $branch = Branch::query()->create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'code' => $data['code'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'delivery_radius_km' => $data['delivery_radius_km'] ?? null,
            'opening_time' => $data['opening_time'] ?? null,
            'closing_time' => $data['closing_time'] ?? null,
            'status' => $data['status'] ?? true,
        ]);

        $this->media->syncSingle($branch, 'logo', $logo);

        app(RolesAndPermissionsSeeder::class)->seedBranchRolesForBranch($branch);

        return $branch;
    }

    public function update(Branch $branch, array $data, ?UploadedFile $logo = null, bool $removeLogo = false): Branch
    {
        $branch->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'code' => $data['code'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'delivery_radius_km' => $data['delivery_radius_km'] ?? null,
            'opening_time' => $data['opening_time'] ?? null,
            'closing_time' => $data['closing_time'] ?? null,
            'status' => $data['status'] ?? true,
        ]);

        $this->media->syncSingle($branch, 'logo', $logo, $removeLogo);

        return $branch->refresh();
    }

    /**
     * @param  array{name: string, phone: string, email?: string|null, password: string}  $data
     */
    public function createSuperAdmin(Branch $branch, array $data): User
    {
        if ($this->hasSuperAdmin($branch)) {
            throw new BranchAlreadyHasSuperAdminException;
        }

        return DB::transaction(function () use ($branch, $data): User {
            app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);

            try {
                $superAdmin = User::query()->create([
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'email' => $data['email'] ?? null,
                    'password' => $data['password'],
                    'user_type' => UserType::BRANCH,
                    'branch_id' => $branch->id,
                    'is_active' => true,
                    'phone_verified_at' => now(),
                ]);

                $superAdmin->assignRole(RoleEnum::SUPER_ADMIN->value);

                return $superAdmin;
            } finally {
                app(PermissionRegistrar::class)->setPermissionsTeamId(null);
            }
        });
    }

    public function hasSuperAdmin(Branch $branch): bool
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);

        try {
            return User::query()
                ->where('user_type', UserType::BRANCH)
                ->where('branch_id', $branch->id)
                ->role(RoleEnum::SUPER_ADMIN->value, 'branch')
                ->exists();
        } finally {
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        }
    }

    /**
     * @return list<array{id: int, name: string, phone: string, email: string|null, is_active: bool}>
     */
    public function listSuperAdmins(Branch $branch): array
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);

        try {
            return User::query()
                ->where('user_type', UserType::BRANCH)
                ->where('branch_id', $branch->id)
                ->role(RoleEnum::SUPER_ADMIN->value, 'branch')
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'email', 'is_active'])
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                ])
                ->values()
                ->all();
        } finally {
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        }
    }

    public function delete(Branch $branch): void
    {
        $branch->delete();
    }

    public function logoUrl(Branch $branch): ?string
    {
        return $branch->logoUrl();
    }
}
