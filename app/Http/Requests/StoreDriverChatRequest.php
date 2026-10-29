<?php

namespace App\Http\Requests;

use App\Enums\DriverAssignmentStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Opens (or resumes) a shared branch↔driver ops chat.
 */
class StoreDriverChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $branch = $this->route('branch');

        if ($branch instanceof Branch) {
            return [
                'driver_id' => [
                    'required',
                    'integer',
                    Rule::exists('users', 'id')->where(
                        fn ($query) => $query
                            ->where('user_type', UserType::DRIVER->value)
                            ->where('is_active', true)
                            ->whereExists(
                                fn ($sub) => $sub->selectRaw('1')
                                    ->from('driver_branch_assignments')
                                    ->whereColumn('driver_branch_assignments.driver_id', 'users.id')
                                    ->where('driver_branch_assignments.branch_id', $branch->id)
                                    ->where('driver_branch_assignments.status', DriverAssignmentStatus::Active->value)
                                    ->whereNull('driver_branch_assignments.ended_at'),
                            ),
                    ),
                ],
            ];
        }

        return [
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(
                    fn ($query) => $query->whereExists(
                        fn ($sub) => $sub->selectRaw('1')
                            ->from('driver_branch_assignments')
                            ->whereColumn('driver_branch_assignments.branch_id', 'branches.id')
                            ->where('driver_branch_assignments.driver_id', $this->user('driver')?->id)
                            ->where('driver_branch_assignments.status', DriverAssignmentStatus::Active->value)
                            ->whereNull('driver_branch_assignments.ended_at'),
                    ),
                ),
            ],
        ];
    }

    public function driver(): User
    {
        return User::query()->findOrFail($this->integer('driver_id'));
    }

    public function branch(): Branch
    {
        $routeBranch = $this->route('branch');

        if ($routeBranch instanceof Branch) {
            return $routeBranch;
        }

        return Branch::query()->findOrFail($this->integer('branch_id'));
    }
}
