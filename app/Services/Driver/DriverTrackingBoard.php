<?php

namespace App\Services\Driver;

use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverAvailabilityStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\OrderFulfillment;
use App\Models\User;
use App\Support\DateTimeFormat;
use App\Support\DriverAvailability;

/**
 * Snapshot behind the live driver maps: who is on duty, where they last were, and what they carry.
 *
 * Built from a handful of batched queries plus the cache, never one query per driver, so the
 * page stays cheap with many drivers. Live movement after the snapshot arrives over Reverb.
 */
class DriverTrackingBoard
{
    public function __construct(private readonly DriverLocationService $locations) {}

    /**
     * @param  list<int>|null  $branchIds  null = every branch
     * @return array{
     *     drivers: list<array<string, mixed>>,
     *     branches: list<array{id: int, name: string}>,
     *     stale_after_seconds: int,
     *     off_duty_count: int
     * }
     */
    public function snapshot(?array $branchIds = null): array
    {
        $assignments = DriverBranchAssignment::query()
            ->where('status', DriverAssignmentStatus::Active)
            ->when($branchIds !== null, fn ($query) => $query->whereIn('branch_id', $branchIds))
            ->get(['driver_id', 'branch_id']);

        $branchesByDriver = $assignments
            ->groupBy('driver_id')
            ->map(fn ($rows): array => $rows->pluck('branch_id')->map(fn ($id): int => (int) $id)->unique()->values()->all());

        $drivers = User::query()
            ->whereIn('id', $branchesByDriver->keys())
            ->where('user_type', UserType::DRIVER)
            ->where('is_active', true)
            ->with([
                'driverProfile',
                'shiftDays' => fn ($query) => $query
                    ->with('shiftTemplate')
                    ->onDuty()
                    ->whereDate('duty_on', DateTimeFormat::at()->toDateString()),
            ])
            ->orderBy('name')
            ->get();

        $deliveries = OrderFulfillment::query()
            ->whereIn('driver_id', $drivers->pluck('id'))
            ->whereIn('status', FulfillmentStatus::driverWorkload())
            ->with('order:id,order_number')
            ->orderByDesc('id')
            ->get()
            ->unique('driver_id')
            ->keyBy('driver_id');

        $staleAfter = (int) config('drivers.tracking.stale_after_seconds');
        $now = now()->getTimestamp();
        $rows = [];

        foreach ($drivers as $driver) {
            $onBreak = $driver->driverProfile?->availability_status === DriverAvailabilityStatus::OnBreak;
            $delivery = $deliveries->get($driver->id);

            if ($onBreak || (! DriverAvailability::onShift($driver) && $delivery === null)) {
                continue;
            }

            $position = $this->locations->lastPosition($driver->id);

            $rows[] = [
                'id' => $driver->id,
                'name' => $driver->name,
                'phone' => $driver->phone,
                'avatar_url' => $driver->avatarUrl(),
                'vehicle_type' => $driver->driverProfile?->vehicle_type?->value,
                'branch_ids' => $branchesByDriver->get($driver->id, []),
                'signal' => match (true) {
                    $position === null => 'none',
                    $position['ts'] >= $now - $staleAfter => 'live',
                    default => 'stale',
                },
                'position' => $position === null ? null : [
                    'lat' => $position['lat'],
                    'lng' => $position['lng'],
                    'ts' => $position['ts'],
                    'heading' => $position['heading'],
                    'speed' => $position['speed'],
                    'accuracy' => $position['accuracy'],
                ],
                'order' => $delivery === null ? null : [
                    'number' => $delivery->order?->order_number,
                    'status' => $delivery->status->value,
                    'status_label' => $delivery->status->label(),
                ],
            ];
        }

        $shownBranchIds = collect($rows)->pluck('branch_ids')->flatten()->unique()->values();

        return [
            'drivers' => $rows,
            'branches' => Branch::query()
                ->whereIn('id', $branchIds ?? $shownBranchIds->all())
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Branch $branch): array => ['id' => $branch->id, 'name' => $branch->name])
                ->all(),
            'stale_after_seconds' => $staleAfter,
            'off_duty_count' => max($drivers->count() - count($rows), 0),
        ];
    }
}
