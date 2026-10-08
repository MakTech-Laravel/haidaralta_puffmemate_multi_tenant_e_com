<?php

namespace App\Services\Order;

use App\Enums\DriverAssignmentStatus;
use App\Enums\UserType;
use App\Models\OrderFulfillment;
use App\Models\User;
use App\Support\Phone;

class OrderAssignService
{
    public function __construct(
        private DriverAssignmentService $drivers,
    ) {}

    public function canAssign(OrderFulfillment $fulfillment): bool
    {
        return $this->drivers->canAssign($fulfillment);
    }

    public function assign(OrderFulfillment $fulfillment, User $driver, User $actor, ?string $reason = null): OrderFulfillment
    {
        return $this->drivers->assign($fulfillment, $driver, $actor, $reason);
    }

    /**
     * @return list<array{id: int, name: string, phone: string, avatar_url: string|null}>
     */
    public function candidatesPayload(OrderFulfillment $fulfillment): array
    {
        return $this->drivers->candidates($fulfillment)
            ->map(fn (User $driver): array => [
                'id' => $driver->id,
                'name' => $driver->name,
                'phone' => $driver->phone,
                'avatar_url' => $driver->avatarUrl(),
            ])
            ->values()
            ->all();
    }

    /**
     * Paginated branch drivers for the Assign driver sheet (name ASC, searchable).
     *
     * @return array{
     *     data: list<array{id: int, name: string, phone: string, avatar_url: string|null}>,
     *     meta: array{current_page: int, last_page: int, per_page: int, total: int},
     *     current_driver_id: int|null
     * }
     */
    public function paginatedCandidatesPayload(
        OrderFulfillment $fulfillment,
        ?string $search,
        int $page,
        int $perPage,
    ): array {
        $perPage = min(50, max(10, $perPage));
        $page = max(1, $page);
        $search = trim((string) $search);

        $query = User::query()
            ->where('user_type', UserType::DRIVER)
            ->where('is_active', true)
            ->whereHas('driverBranchAssignments', function ($assignments) use ($fulfillment): void {
                $assignments->where('branch_id', $fulfillment->branch_id)
                    ->where('status', DriverAssignmentStatus::Active)
                    ->where(function ($dates): void {
                        $dates->whereNull('ended_at')->orWhereDate('ended_at', '>=', now()->toDateString());
                    });
            })
            ->with('media')
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($filters) use ($search): void {
                $filters->where('name', 'like', "%{$search}%");
                Phone::constrainLike($filters, 'phone', $search);
            });
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->getCollection()
                ->map(fn (User $driver): array => [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'phone' => $driver->phone,
                    'avatar_url' => $driver->avatarUrl(),
                ])
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'current_driver_id' => $fulfillment->driver_id,
        ];
    }

    /**
     * @return array{assign_status: 'not_assign'|'assign', driver_id: int|null, driver_name: string|null}
     */
    public function assignStatusPayload(OrderFulfillment $fulfillment): array
    {
        $driverId = $fulfillment->driver_id;

        if ($driverId === null) {
            return [
                'assign_status' => 'not_assign',
                'driver_id' => null,
                'driver_name' => null,
            ];
        }

        $driverName = $fulfillment->relationLoaded('driver')
            ? $fulfillment->driver?->name
            : User::query()->whereKey($driverId)->value('name');

        return [
            'assign_status' => 'assign',
            'driver_id' => (int) $driverId,
            'driver_name' => $driverName,
        ];
    }
}
