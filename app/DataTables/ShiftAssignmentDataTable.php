<?php

namespace App\DataTables;

use App\Enums\ShiftAssignmentStatus;
use App\Enums\ShiftDayKind;
use App\Enums\ShiftOwnerType;
use App\Enums\UserType;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftDay;
use App\Support\DateTimeFormat;
use App\Support\Phone;
use App\Support\WorkforcePayload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/**
 * @extends AbstractDataTable<User>
 */
class ShiftAssignmentDataTable extends AbstractDataTable
{
    private ?string $resolvedMonth = null;

    public function __construct(
        Request $request,
        protected ShiftOwnerType $owner = ShiftOwnerType::Platform,
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
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = parent::toArray();
        $payload['month'] = $this->month();

        return $payload;
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
        if ($this->owner === ShiftOwnerType::Branch && $this->branchId === null) {
            return User::query()->whereRaw('1 = 0');
        }

        $templateId = $this->shiftTemplateFilterId();

        return User::query()
            ->select(['users.id', 'users.name', 'users.phone', 'users.user_type', 'users.branch_id'])
            ->whereHas('shiftDays', function (Builder $days) use ($templateId): void {
                $this->constrainDutyDays($days, $templateId);
            })
            ->with([
                'media',
                'shiftDays' => function ($days) use ($templateId): void {
                    $this->constrainDutyDays($days->getQuery(), $templateId);
                    $days->orderBy('duty_on');
                },
            ]);
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::callback('shift_template_id', function (): void {
                // Applied in baseQuery via shiftTemplateFilterId().
            }),
            AllowedFilter::callback('people_kind', function (Builder $query, mixed $value): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                if ($value === UserType::DRIVER->value) {
                    $query->where('user_type', UserType::DRIVER);

                    return;
                }

                $query->where('user_type', '!=', UserType::DRIVER);
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

            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('name', 'like', '%'.$term.'%');
                Phone::constrainLike($builder, 'phone', $term);
            });
        });
    }

    /**
     * @return list<AllowedSort|string>
     */
    protected function allowedSorts(): array
    {
        return [
            AllowedSort::field('name'),
            AllowedSort::field('id'),
        ];
    }

    protected function defaultSort(): string
    {
        return 'name';
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
     * @param  User  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var User $model */
        $dates = [];

        foreach ($model->shiftDays as $day) {
            $dates[] = $day->duty_on->format('Y-m-d');
        }

        $dates = array_values(array_unique($dates));
        sort($dates);

        $firstDay = $model->shiftDays->first();
        $branchId = $this->branchId
            ?? ($firstDay instanceof UserShiftDay ? $firstDay->branch_id : null)
            ?? $model->branch_id;

        return WorkforcePayload::assignedPersonMonth(
            $model,
            $this->month(),
            $dates,
            $branchId !== null ? (int) $branchId : null,
        );
    }

    public function month(): string
    {
        if ($this->resolvedMonth !== null) {
            return $this->resolvedMonth;
        }

        $candidate = trim((string) $this->request->input('month', ''));
        $parsed = $candidate !== ''
            ? Carbon::createFromFormat('!Y-m', $candidate)
            : null;

        if ($parsed === null || $parsed->format('Y-m') !== $candidate) {
            $this->resolvedMonth = DateTimeFormat::now()->format('Y-m');

            return $this->resolvedMonth;
        }

        $this->resolvedMonth = $parsed->format('Y-m');

        return $this->resolvedMonth;
    }

    /**
     * @param  Builder<UserShiftDay>|Builder<Model>  $days
     */
    private function constrainDutyDays(Builder $days, ?int $templateId): void
    {
        $month = $this->month();
        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth()->toDateString();
        $end = Carbon::createFromFormat('!Y-m', $month)->endOfMonth()->toDateString();

        $days
            ->whereIn('kind', [
                ShiftDayKind::Scheduled,
                ShiftDayKind::Override,
            ])
            ->whereNotNull('shift_template_id')
            ->whereDate('duty_on', '>=', $start)
            ->whereDate('duty_on', '<=', $end)
            ->when(
                $this->branchId !== null,
                fn (Builder $query) => $query->where('branch_id', $this->branchId),
            )
            ->when(
                $templateId !== null,
                fn (Builder $query) => $query->where('shift_template_id', $templateId),
            )
            ->whereHas(
                'assignment',
                fn (Builder $assignments) => $assignments->where('status', ShiftAssignmentStatus::Scheduled),
            )
            ->when(
                ! $this->allTemplatesAtBranch,
                function (Builder $query): void {
                    $templateIds = ShiftTemplate::query()
                        ->when(
                            $this->owner === ShiftOwnerType::Platform,
                            fn (Builder $templates) => $templates->platformOwned(),
                            fn (Builder $templates) => $templates->branchOwned(),
                        )
                        ->select('id');

                    $query->whereIn('shift_template_id', $templateIds);
                },
            );
    }

    private function shiftTemplateFilterId(): ?int
    {
        $raw = data_get($this->request->input('filter'), 'shift_template_id');

        if ($raw === null || $raw === '' || $raw === 'all') {
            return null;
        }

        $id = (int) $raw;

        return $id > 0 ? $id : null;
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function templateOptions(): array
    {
        if ($this->owner === ShiftOwnerType::Branch && $this->branchId === null) {
            return [];
        }

        return array_values(ShiftTemplate::query()
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
            ->all());
    }
}
