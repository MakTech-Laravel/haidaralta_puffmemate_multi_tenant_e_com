<?php

namespace App\Http\Controllers\Platform;

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreBranchRequest;
use App\Http\Requests\Platform\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\User;
use App\Support\Toast;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

class BranchController extends Controller
{
    /**
     * Display a listing of branches.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        $branches = Branch::query()
            ->withCount('users')
            ->when(
                $search !== '',
                fn ($query) => $query->where(function ($builder) use ($search): void {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                }),
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Branch $branch): array => $this->mapBranchRow($branch));

        return Inertia::render('platform/branches/index', [
            'branches' => $branches,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show the form for creating a branch.
     */
    public function create(): Response
    {
        return Inertia::render('platform/branches/create');
    }

    /**
     * Store a newly created branch and its required super-admin.
     */
    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $branch = DB::transaction(function () use ($request): Branch {
            $branch = Branch::query()->create([
                'name' => $request->validated('name'),
                'slug' => $request->validated('slug'),
                'icon' => $this->storeIcon($request->file('icon')),
                'address' => $request->validated('address'),
                'latitude' => $request->validated('latitude'),
                'longitude' => $request->validated('longitude'),
                'is_active' => $request->boolean('is_active', true),
            ]);

            app(RolesAndPermissionsSeeder::class)->seedBranchRolesForBranch($branch);

            app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);

            $superAdmin = User::query()->create([
                'name' => $request->validated('super_admin_name'),
                'email' => $request->validated('super_admin_email'),
                'password' => $request->validated('super_admin_password'),
                'user_type' => UserType::BRANCH,
                'branch_id' => $branch->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $superAdmin->assignRole(RoleEnum::SUPER_ADMIN->value);

            app(PermissionRegistrar::class)->setPermissionsTeamId(null);

            return $branch;
        });

        Toast::success('Branch created.');

        return redirect()->route('platform.branches.show', $branch);
    }

    /**
     * Display the specified branch.
     */
    public function show(Branch $branch): Response
    {
        $branch->loadCount('users');

        app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);

        $superAdmins = User::query()
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

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        return Inertia::render('platform/branches/show', [
            'branch' => [
                ...$this->mapBranchRow($branch),
                'address' => $branch->address,
                'latitude' => $branch->latitude,
                'longitude' => $branch->longitude,
                'super_admins' => $superAdmins,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified branch.
     */
    public function edit(Branch $branch): Response
    {
        return Inertia::render('platform/branches/edit', [
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'slug' => $branch->slug,
                'icon' => $branch->icon,
                'icon_url' => $this->iconUrl($branch->icon),
                'address' => $branch->address,
                'latitude' => $branch->latitude,
                'longitude' => $branch->longitude,
                'is_active' => $branch->is_active,
            ],
        ]);
    }

    /**
     * Update the specified branch.
     */
    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        $iconPath = $branch->icon;

        if ($request->boolean('remove_icon')) {
            $this->deleteIcon($iconPath);
            $iconPath = null;
        } elseif ($request->hasFile('icon')) {
            $this->deleteIcon($iconPath);
            $iconPath = $this->storeIcon($request->file('icon'));
        }

        $branch->update([
            'name' => $request->validated('name'),
            'slug' => $request->validated('slug'),
            'icon' => $iconPath,
            'address' => $request->validated('address'),
            'latitude' => $request->validated('latitude'),
            'longitude' => $request->validated('longitude'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        Toast::success('Branch updated.');

        return redirect()->route('platform.branches.show', $branch);
    }

    /**
     * Remove the specified branch.
     */
    public function destroy(Branch $branch): RedirectResponse
    {
        if ($branch->users()->exists()) {
            Toast::error('Cannot delete a branch that still has staff accounts.');

            return redirect()->back();
        }

        $this->deleteIcon($branch->icon);
        $branch->delete();

        Toast::success('Branch deleted.');

        return redirect()->route('platform.branches.index');
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     icon: string|null,
     *     icon_url: string|null,
     *     is_active: bool,
     *     users_count: int
     * }
     */
    private function mapBranchRow(Branch $branch): array
    {
        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'slug' => $branch->slug,
            'icon' => $branch->icon,
            'icon_url' => $this->iconUrl($branch->icon),
            'is_active' => $branch->is_active,
            'users_count' => (int) ($branch->users_count ?? $branch->users()->count()),
        ];
    }

    private function storeIcon(?UploadedFile $file): ?string
    {
        if ($file === null) {
            return null;
        }

        return $file->store('branches', 'public');
    }

    private function deleteIcon(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        // Ignore legacy Lucide icon name strings that are not stored files.
        if (! str_contains($path, '/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private function iconUrl(?string $path): ?string
    {
        if ($path === null || $path === '' || ! str_contains($path, '/')) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
