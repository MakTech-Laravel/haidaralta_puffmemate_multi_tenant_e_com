<?php

namespace App\DataTables;

use App\DataTables\Filters\StatusBooleanFilter;
use App\Enums\DriverRosterStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
use App\Support\DriverAvailability;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\AllowedFilter;

class DriverDataTable extends AbstractDataTable
{
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
        $today = now()->toDateString();

        return User::query()
            ->where('user_type', UserType::DRIVER)
            ->with([
                'driverProfile',
                'driverBranchAssignments.branch:id,name,slug',
                'driverRosters' => function ($query) use ($today): void {
                    $query
                        ->where('status', DriverRosterStatus::Scheduled->value)
                        ->whereDate('start_date', '<=', $today)
                        ->whereDate('end_date', '>=', $today);
                },
            ]);
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
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
            AllowedFilter::callback('branch_id', function (Builder $query, mixed $value): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                $query->whereHas(
                    'driverBranchAssignments',
                    fn (Builder $assignments): Builder => $assignments
                        ->where('branch_id', (int) $value)
                        ->where('status', 'active'),
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
            'branches' => Branch::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Branch $branch): array => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'slug' => $branch->slug,
                ])
                ->all(),
            'availability_status' => [
                ['value' => 'all', 'label' => 'All availability'],
                ['value' => 'available', 'label' => 'Available'],
                ['value' => 'busy', 'label' => 'Busy'],
                ['value' => 'offline', 'label' => 'Offline'],
                ['value' => 'on_break', 'label' => 'On break'],
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
        $effective = DriverAvailability::effectivePayload($model);

        return [
            'id' => $model->id,
            'name' => $model->name,
            'phone' => $model->phone,
            'email' => $model->email,
            'is_active' => (bool) $model->is_active,
            'avatar_url' => MediaUrl::resolve($profile?->avatar_path, fallback: null),
            'vehicle_type' => $profile?->vehicle_type?->value,
            'vehicle_type_label' => $profile?->vehicle_type?->label(),
            'availability_status' => $profile?->availability_status?->value,
            'availability_status_label' => $profile?->availability_status?->label(),
            'effective_availability' => $effective['value'],
            'effective_availability_label' => $effective['label'],
            'branch_ids' => $model->driverBranchAssignments
                ->filter(fn ($assignment) => $assignment->status?->value === 'active')
                ->pluck('branch_id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all(),
            'branches' => $model->driverBranchAssignments
                ->filter(fn ($assignment) => $assignment->status?->value === 'active')
                ->map(fn ($assignment): array => [
                    'id' => $assignment->branch?->id,
                    'name' => $assignment->branch?->name,
                    'slug' => $assignment->branch?->slug,
                ])
                ->values()
                ->all(),
        ];
    }
}
