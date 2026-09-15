<?php

namespace App\DataTables;

use App\DataTables\Filters\StatusBooleanFilter;
use App\Enums\DriverAssignmentStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;

class BranchDriverDataTable extends AbstractDataTable
{
    public function __construct(
        Request $request,
        protected Branch $branch,
    ) {
        parent::__construct($request);
    }

    /**
     * @return array<string, mixed>
     */
    public static function makeForBranch(Request $request, Branch $branch): array
    {
        return (new static($request, $branch))->toArray();
    }

    /**
     * @return class-string<User>
     */
    protected function modelClass(): string
    {
        return User::class;
    }

    /**
     * @return Builder<User>
     */
    protected function baseQuery(): Builder
    {
        $branchId = $this->branch->id;

        return User::query()
            ->where('user_type', UserType::DRIVER)
            ->whereHas(
                'driverBranchAssignments',
                fn (Builder $assignments): Builder => $assignments->where('branch_id', $branchId),
            )
            ->with([
                'driverProfile',
                'driverBranchAssignments' => fn ($query) => $query->where('branch_id', $branchId),
            ]);
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        $branchId = $this->branch->id;

        return [
            StatusBooleanFilter::make('is_active'),
            AllowedFilter::callback('availability_status', function (Builder $query, mixed $value): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                $query->whereHas(
                    'driverProfile',
                    fn (Builder $profile): Builder => $profile->where('availability_status', (string) $value),
                );
            }),
            AllowedFilter::callback('assignment_status', function (Builder $query, mixed $value) use ($branchId): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                $query->whereHas(
                    'driverBranchAssignments',
                    fn (Builder $assignments): Builder => $assignments
                        ->where('branch_id', $branchId)
                        ->where('status', (string) $value),
                );
            }),
            $this->searchFilter(),
        ];
    }

    /**
     * @return list<string>
     */
    protected function modelSearchableColumns(): array
    {
        return ['name', 'phone', 'email'];
    }

    /**
     * @return list<string>
     */
    protected function allowedSorts(): array
    {
        return [
            'name',
            'phone',
            'email',
            'is_active',
            'created_at',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterOptions(): array
    {
        return [
            'availability_status' => [
                ['value' => 'all', 'label' => 'All availability'],
                ['value' => 'available', 'label' => 'Available'],
                ['value' => 'busy', 'label' => 'Busy'],
                ['value' => 'offline', 'label' => 'Offline'],
                ['value' => 'on_break', 'label' => 'On break'],
            ],
            'assignment_status' => [
                ['value' => 'all', 'label' => 'All assignments'],
                ['value' => DriverAssignmentStatus::Active->value, 'label' => 'Active'],
                ['value' => DriverAssignmentStatus::Inactive->value, 'label' => 'Inactive'],
                ['value' => DriverAssignmentStatus::Suspended->value, 'label' => 'Suspended'],
            ],
            'is_active' => [
                ['value' => 'all', 'label' => 'All status'],
                ['value' => '1', 'label' => 'Active'],
                ['value' => '0', 'label' => 'Inactive'],
            ],
        ];
    }

    /**
     * @param  User  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var User $model */
        $profile = $model->driverProfile;
        $assignment = $model->driverBranchAssignments->first();

        return [
            'id' => $model->id,
            'name' => $model->name,
            'phone' => $model->phone,
            'email' => $model->email,
            'is_active' => (bool) $model->is_active,
            'vehicle_type' => $profile?->vehicle_type?->value,
            'vehicle_type_label' => $profile?->vehicle_type?->label(),
            'availability_status' => $profile?->availability_status?->value,
            'availability_status_label' => $profile?->availability_status?->label(),
            'assignment_status' => $assignment?->status?->value,
            'assignment_status_label' => $assignment?->status?->label(),
        ];
    }
}
