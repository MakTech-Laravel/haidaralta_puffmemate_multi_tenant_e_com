<?php

namespace App\Services\Branch;

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Exceptions\BranchAlreadyHasSuperAdminException;
use App\Models\Branch;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

class BranchService
{
    /**
     * @param  array{
     *     name: string,
     *     slug: string,
     *     code: string,
     *     phone?: string|null,
     *     email?: string|null,
     *     address?: string|null,
     *     latitude?: float|string|null,
     *     longitude?: float|string|null,
     *     opening_time?: string|null,
     *     closing_time?: string|null,
     *     status?: bool
     * }  $data
     */
    public function create(array $data, ?UploadedFile $logo = null): Branch
    {
        return DB::transaction(function () use ($data, $logo): Branch {
            $branch = Branch::query()->create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'code' => $data['code'],
                'logo' => $this->storeLogo($logo),
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'opening_time' => $data['opening_time'] ?? null,
                'closing_time' => $data['closing_time'] ?? null,
                'status' => $data['status'] ?? true,
            ]);

            app(RolesAndPermissionsSeeder::class)->seedBranchRolesForBranch($branch);

            return $branch;
        });
    }

    /**
     * @param  array{
     *     name: string,
     *     slug: string,
     *     code: string,
     *     phone?: string|null,
     *     email?: string|null,
     *     address?: string|null,
     *     latitude?: float|string|null,
     *     longitude?: float|string|null,
     *     opening_time?: string|null,
     *     closing_time?: string|null,
     *     status?: bool
     * }  $data
     */
    public function update(Branch $branch, array $data, ?UploadedFile $logo = null, bool $removeLogo = false): Branch
    {
        $logoPath = $branch->logo;

        if ($removeLogo) {
            $this->deleteLogo($logoPath);
            $logoPath = null;
        } elseif ($logo !== null) {
            $this->deleteLogo($logoPath);
            $logoPath = $this->storeLogo($logo);
        }

        $branch->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'code' => $data['code'],
            'logo' => $logoPath,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'opening_time' => $data['opening_time'] ?? null,
            'closing_time' => $data['closing_time'] ?? null,
            'status' => $data['status'] ?? true,
        ]);

        return $branch->refresh();
    }

    /**
     * @param  array{name: string, email: string, password: string}  $data
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
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'user_type' => UserType::BRANCH,
                    'branch_id' => $branch->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
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
     * @return list<array{id: int, name: string, email: string, is_active: bool}>
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
                ->get(['id', 'name', 'email', 'is_active'])
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
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
        $this->deleteLogo($branch->logo);
        $branch->delete();
    }

    public function logoUrl(?string $path): ?string
    {
        if ($path === null || $path === '' || ! str_contains($path, '/')) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    private function storeLogo(?UploadedFile $file): ?string
    {
        if ($file === null) {
            return null;
        }

        return $file->store('branches', 'public');
    }

    private function deleteLogo(?string $path): void
    {
        if ($path === null || $path === '' || ! str_contains($path, '/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
