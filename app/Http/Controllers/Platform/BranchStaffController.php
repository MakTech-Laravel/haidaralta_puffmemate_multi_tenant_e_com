<?php

namespace App\Http\Controllers\Platform;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

class BranchStaffController extends Controller
{
    /**
     * Display branch staff across all branches for platform oversight.
     */
    public function index(Request $request): Response
    {
        $branchId = $request->integer('branch_id') ?: null;

        $staff = User::query()
            ->where('user_type', UserType::BRANCH)
            ->with('branch:id,name,slug')
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(function (User $user): array {
                $roles = [];

                if ($user->branch_id !== null) {
                    app(PermissionRegistrar::class)->setPermissionsTeamId($user->branch_id);
                    $roles = $user->getRoleNames()->values()->all();
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'branch' => $user->branch?->only(['id', 'name', 'slug']),
                    'roles' => $roles,
                ];
            });

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        return Inertia::render('platform/branch-staff/index', [
            'staff' => $staff,
            'branches' => Branch::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'filters' => [
                'branch_id' => $branchId,
            ],
        ]);
    }

    /**
     * Display a single branch staff member.
     */
    public function show(User $user): Response
    {
        if ($user->user_type !== UserType::BRANCH) {
            abort(404);
        }

        $user->load('branch:id,name,slug');

        $roles = [];

        if ($user->branch_id !== null) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->branch_id);
            $roles = $user->getRoleNames()->values()->all();
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        return Inertia::render('platform/branch-staff/show', [
            'staffMember' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'branch' => $user->branch?->only(['id', 'name', 'slug']),
                'roles' => $roles,
            ],
        ]);
    }
}
