<?php

namespace App\DataTables;

use App\Enums\ShiftAssignmentStatus;
use App\Enums\ShiftOwnerType;
use App\Enums\UserType;
use App\Models\ShiftTemplate;
use App\Models\UserShiftAssignment;
use App\Support\WorkforcePayload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/**
 * @extends AbstractDataTable<UserShiftAssignment>
 */
class ShiftAssignmentDataTable extends AbstractDataTable
{
    public function __construct(
        Request $request,
        protected ShiftOwnerType $owner,
        protected ?int $branchId = null,
        protected bool $allTemplatesAtBranch = false,
    ) {
        parent::__construct($request);
    }

    /**
     * Platform-owned shifts assigned to people.
     *
     * @return array<string, mixed>
     */
    public static function forPlatform(Request $request): array
    {
        return (new static($request, ShiftOwnerType::Platform))->toArray();
    }

    /**
     * Branch-owned shifts at one store. Empty until a store is chosen.
     *
     * @return array<string, mixed>
     */
    public static function forBranchOwner(Request $request, ?int $branchId): array
    {
        return (new static($request, ShiftOwnerType::Branch, $branchId))->toArray();
    }

    /**
     * Every assignment at a branch portal store, including platform-owned shifts.
     *
     * @return array<string, mixed>
     */
    public static function forBranchPortal(Request $request, int $branchId): array
    {
        return (new static($request, ShiftOwnerType::Branch, $branchId, true))->toArray();
    }

    /**
     * @return class-string<UserShiftAssignment>
     */
    protected function modelClass(): string
    {
        return UserShiftAssignment::class;
    }

    /**
     * @return Builder<UserShiftAssignment>
     */
    protected function baseQuery(): Builder
    {
        if ($this->owner === ShiftOwnerType::Branch && $this->branchId === null) {
            return UserShiftAssignment::query()->whereRaw('1 = 0');
        }

        return UserShiftAssignment::query()
            ->with(['shiftTemplate:id,name,start_time,end_time,owner_type,branch_id', 'branch:id,name,slug', 'user:id,name,avatar,user_type'])
            ->where('status', ShiftAssignmentStatus::Scheduled)
            ->when($this->branchId !== null, fn (Builder $query) => $query->where('branch_id', $this->branchId))
            ->when(
                ! $this->allTemplatesAtBranch,
                fn (Builder $query) => $query->whereHas(
                    'shiftTemplate',
                    function (Builder $templates): void {
                        if ($this->owner === ShiftOwnerType::Platform) {
                            $templates->platformOwned();

                            return;
                        }

                        $templates->branchOwned();
                    },
                ),
            );
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::exact('shift_template_id'),
            AllowedFilter::callback('people_kind', function (Builder $query, mixed $value): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                if ($value === UserType::DRIVER->value) {
                    $query->whereHas(
                        'user',
                        fn (Builder $users) => $users->where('user_type', UserType::DRIVER),
                    );

                    return;
                }

                $query->whereHas(
                    'user',
                    fn (Builder $users) => $users->where('user_type', '!=', UserType::DRIVER),
                );
            }),
            $this->searchFilter(),
        ];
    }

    protected function searchFilter(): AllowedFilter
    {
        return AllowedFilter::callback('search', function (Builder $query, mixed $value): void {
            $term = trim((string) $value);

            if ($term === '') {
                return;
            }

            $like = '%'.$term.'%';

            $query->where(function (Builder $builder) use ($like): void {
                $builder
                    ->whereHas('user', fn (Builder $users) => $users->where('name', 'like', $like))
                    ->orWhereHas('shiftTemplate', fn (Builder $templates) => $templates->where('name', 'like', $like));
            });
        });
    }

    /**
     * @return list<AllowedSort|string>
     */
    protected function allowedSorts(): array
    {
        return ['start_date', 'end_date', 'id'];
    }

    protected function defaultSort(): string
    {
        return '-start_date';
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterOptions(): array
    {
        return [
            'templates' => $this->templateOptions(),
            'people_kinds' => [
                ['value' => 'all', 'label' => 'All people'],
                ['value' => 'staff', 'label' => 'Staff'],
                ['value' => 'driver', 'label' => 'Drivers'],
            ],
        ];
    }

    /**
     * @param  UserShiftAssignment  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var UserShiftAssignment $model */
        return WorkforcePayload::assignedPerson($model);
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function templateOptions(): array
    {
        if ($this->owner === ShiftOwnerType::Branch && $this->branchId === null) {
            return [];
        }

        return ShiftTemplate::query()
            ->when(
                $this->allTemplatesAtBranch && $this->branchId !== null,
                fn (Builder $query) => $query->availableToBranch($this->branchId),
                function (Builder $query): void {
                    if ($this->owner === ShiftOwnerType::Platform) {
                        $query->platformOwned();

                        return;
                    }

                    $query->branchOwned()->where('branch_id', $this->branchId);
                },
            )
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ShiftTemplate $template): array => [
                'id' => $template->id,
                'name' => $template->name,
            ])
            ->all();
    }
}
