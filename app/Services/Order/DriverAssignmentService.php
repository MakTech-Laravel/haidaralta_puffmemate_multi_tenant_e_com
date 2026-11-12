<?php

namespace App\Services\Order;

use App\Contracts\DistanceCalculator;
use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverAvailabilityStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\NotificationType;
use App\Enums\ShiftAssignmentStatus;
use App\Enums\UserType;
use App\Events\DriverOrdersChanged;
use App\Models\OrderDriverAssignmentHistory;
use App\Models\OrderFulfillment;
use App\Models\User;
use App\Models\UserShiftDay;
use App\Services\Notifications\NotificationService;
use App\Support\DriverAvailability;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DriverAssignmentService
{
    public function __construct(
        private FulfillmentStatusService $statuses,
        private DistanceCalculator $distances,
        private NotificationService $notifications,
    ) {}

    /**
     * @return Collection<int, User>
     */
    public function candidates(OrderFulfillment $fulfillment): Collection
    {
        $window = $this->deliveryWindow($fulfillment);
        $workload = $this->workload($window['date'] ?? null);

        $drivers = User::query()
            ->where('user_type', UserType::DRIVER)
            ->where('is_active', true)
            ->whereHas('driverBranchAssignments', function ($query) use ($fulfillment): void {
                $query->where('branch_id', $fulfillment->branch_id)
                    ->where('status', DriverAssignmentStatus::Active)
                    ->where(function ($dates): void {
                        $dates->whereNull('ended_at')->orWhereDate('ended_at', '>=', now()->toDateString());
                    });
            })
            ->with(['driverProfile', 'media'])
            ->get();

        $onDutyIds = $window === null ? [] : $this->driverIdsOnDutyDuring($drivers->pluck('id')->all(), $window);

        return $drivers
            ->filter(fn (User $driver): bool => $window === null
                ? DriverAvailability::effective($driver) === DriverAvailabilityStatus::Available
                : $this->availableForWindow($driver, $window, $onDutyIds))
            ->sortBy(function (User $driver) use ($fulfillment, $workload): array {
                $available = $driver->driverProfile?->availability_status === DriverAvailabilityStatus::Available ? 0 : 1;
                $load = (int) ($workload[$driver->id] ?? 0);

                return [$available, $load, $this->distanceFromBranch($fulfillment, $driver), $driver->id];
            })
            ->values();
    }

    /**
     * The customer's delivery day and shift window for this order, or null when the
     * order has none (then availability is judged for right now).
     *
     * @return array{date: string, start: string, end: string, is_today: bool}|null
     */
    public function deliveryWindow(OrderFulfillment $fulfillment): ?array
    {
        $fulfillment->loadMissing('order.deliveryShift');

        $order = $fulfillment->order;
        $shift = $order?->deliveryShift;

        if ($order === null || $order->delivery_date === null || $shift === null) {
            return null;
        }

        $date = $order->delivery_date->toDateString();

        return [
            'date' => $date,
            'start' => $shift->start_time->format('H:i:s'),
            'end' => $shift->end_time->format('H:i:s'),
            'is_today' => $date === now(config('app.timezone'))->toDateString(),
        ];
    }

    /**
     * Open orders per driver id: fulfillments the driver is working (assigned through
     * returning) plus orders still waiting for packing that have the driver reserved.
     * With a delivery date only orders due that day are counted.
     *
     * @return Collection<int, int>
     */
    public function workload(?string $deliveryDate = null): Collection
    {
        $dueOn = fn ($query) => $deliveryDate === null
            ? $query
            : $query->whereHas('order', fn ($order) => $order->whereDate('delivery_date', $deliveryDate));

        $working = $dueOn(OrderFulfillment::query()
            ->whereNotNull('driver_id')
            ->whereIn('status', FulfillmentStatus::driverWorkload()))
            ->pluck('driver_id')
            ->countBy();

        $reserved = $dueOn(OrderFulfillment::query()
            ->whereNotNull('reserved_driver_id')
            ->whereNotIn('status', [FulfillmentStatus::Delivered, FulfillmentStatus::Cancelled]))
            ->pluck('reserved_driver_id')
            ->countBy();

        $totals = [];

        foreach ([$working, $reserved] as $counts) {
            foreach ($counts as $driverId => $count) {
                $totals[$driverId] = ($totals[$driverId] ?? 0) + $count;
            }
        }

        return collect($totals);
    }

    /**
     * Whether staff may assign or reassign a driver for this fulfillment.
     */
    public function canAssign(OrderFulfillment $fulfillment): bool
    {
        if ($fulfillment->driver_id !== null) {
            return $this->driverWasAutoAssigned($fulfillment);
        }

        return in_array($fulfillment->status, [
            FulfillmentStatus::Placed,
            FulfillmentStatus::Processing,
            FulfillmentStatus::Ready,
            FulfillmentStatus::Returned,
        ], true);
    }

    /**
     * An automatically picked driver may still be replaced by staff until pickup;
     * a driver chosen by hand stays fixed.
     */
    private function driverWasAutoAssigned(OrderFulfillment $fulfillment): bool
    {
        if ($fulfillment->status !== FulfillmentStatus::Assigned) {
            return false;
        }

        $latest = $fulfillment->driverAssignmentHistories()->latest('id')->first();

        return $latest !== null
            && (int) $latest->to_driver_id === (int) $fulfillment->driver_id
            && $latest->reason === DriverAutoAssignService::PROMOTION_REASON;
    }

    /**
     * @param  list<int>  $driverIds
     * @param  array{date: string, start: string, end: string, is_today: bool}  $window
     * @return list<int>
     */
    private function driverIdsOnDutyDuring(array $driverIds, array $window): array
    {
        if ($driverIds === []) {
            return [];
        }

        return UserShiftDay::query()
            ->with('shiftTemplate')
            ->whereIn('user_id', $driverIds)
            ->onDuty()
            ->whereDate('duty_on', $window['date'])
            ->whereHas('assignment', fn ($query) => $query->where('status', ShiftAssignmentStatus::Scheduled))
            ->get()
            ->filter(fn (UserShiftDay $day): bool => $day->overlapsWindow($window['start'], $window['end']))
            ->pluck('user_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Rostered during the delivery window. A break or busy override only matters when the
     * delivery is today, because it describes the driver right now.
     *
     * @param  array{date: string, start: string, end: string, is_today: bool}  $window
     * @param  list<int>  $onDutyIds
     */
    private function availableForWindow(User $driver, array $window, array $onDutyIds): bool
    {
        if (! in_array($driver->id, $onDutyIds, true)) {
            return false;
        }

        if (! $window['is_today']) {
            return true;
        }

        $stored = $driver->driverProfile?->availability_status;

        return $stored !== DriverAvailabilityStatus::OnBreak && $stored !== DriverAvailabilityStatus::Busy;
    }

    /**
     * Distance from the branch to the driver's profile address, when it has
     * coordinates. Missing coordinates sort last without excluding the driver.
     */
    private function distanceFromBranch(OrderFulfillment $fulfillment, User $driver): float
    {
        $profile = $driver->driverProfile;
        $branch = $fulfillment->branch;

        if ($profile?->latitude === null || $profile->longitude === null || $branch?->latitude === null || $branch->longitude === null) {
            return PHP_FLOAT_MAX;
        }

        return $this->distances->distanceKm(
            (float) $branch->latitude,
            (float) $branch->longitude,
            (float) $profile->latitude,
            (float) $profile->longitude,
        );
    }

    public function assign(
        OrderFulfillment $fulfillment,
        User $driver,
        ?User $actor,
        ?string $reason = null,
        ?FulfillmentStatus $advance = null,
    ): OrderFulfillment {
        if (! $this->canAssign($fulfillment)) {
            throw ValidationException::withMessages([
                'driver_id' => 'A driver can only be assigned before pickup, or again after the goods are back at the branch.',
            ]);
        }

        if ($fulfillment->driver_id === $driver->id) {
            return $fulfillment;
        }

        $fromDriverId = $fulfillment->driver_id;
        $reason = $reason !== null ? trim($reason) : null;
        $reason = $reason === '' ? null : $reason;

        if ($fromDriverId !== null && $reason === null) {
            throw ValidationException::withMessages([
                'reason' => 'Provide a reason for changing the driver.',
            ]);
        }

        if ($fulfillment->status === FulfillmentStatus::Returned && $reason === null) {
            throw ValidationException::withMessages([
                'reason' => 'Provide a reason when assigning a driver after a return.',
            ]);
        }

        if ($advance !== null && ! in_array($advance, [FulfillmentStatus::PickedUp, FulfillmentStatus::OutForDelivery], true)) {
            throw ValidationException::withMessages([
                'advance' => 'Choose pickup or on the way.',
            ]);
        }

        $allowed = $this->candidates($fulfillment)->pluck('id');

        if (! $allowed->contains($driver->id)) {
            throw ValidationException::withMessages([
                'driver_id' => 'Choose an active driver from this branch.',
            ]);
        }

        // While packing is still ahead, picking another driver only swaps the reservation so the
        // order keeps its normal Placed -> Processing -> Ready flow.
        if ($advance === null
            && $fulfillment->reserved_driver_id !== null
            && in_array($fulfillment->status, [FulfillmentStatus::Placed, FulfillmentStatus::Processing], true)) {
            $fulfillment->forceFill(['reserved_driver_id' => $driver->id])->save();

            return $fulfillment;
        }

        // A real assignment (manual or promoted) always supersedes an auto reservation.
        $fulfillment->forceFill(['driver_id' => $driver->id, 'reserved_driver_id' => null])->save();

        OrderDriverAssignmentHistory::query()->create([
            'order_id' => $fulfillment->order_id,
            'fulfillment_id' => $fulfillment->id,
            'from_driver_id' => $fromDriverId,
            'to_driver_id' => $driver->id,
            'reason' => $reason,
            'changed_by' => $actor?->id,
        ]);

        $this->advanceAfterAssign($fulfillment->refresh(), $actor, $advance);

        $fulfillment->refresh();
        $this->notifyDriver($fulfillment, $driver, $actor);

        if ($fromDriverId !== null) {
            $this->notifyPreviousDriver($fulfillment, $fromDriverId, $actor);
        }

        return $fulfillment;
    }

    /**
     * Tell a driver replaced by staff that the order is no longer theirs.
     */
    private function notifyPreviousDriver(OrderFulfillment $fulfillment, int $driverId, ?User $actor): void
    {
        $orderNumber = (string) $fulfillment->order()->value('order_number');

        $this->notifications->send(
            [$driverId],
            NotificationType::Delivery,
            'Order reassigned',
            'Order '.$orderNumber.' has been given to another driver.',
            [
                'order_number' => $orderNumber,
                'fulfillment_id' => $fulfillment->id,
            ],
            $actor,
        );

        DriverOrdersChanged::dispatch($driverId, $orderNumber, DriverOrdersChanged::REASON_UNASSIGNED);
    }

    /**
     * Inbox notification plus a live refresh signal for the driver's open pages.
     */
    private function notifyDriver(OrderFulfillment $fulfillment, User $driver, ?User $actor): void
    {
        $orderNumber = (string) $fulfillment->order()->value('order_number');

        $this->notifications->send(
            [$driver->id],
            NotificationType::Delivery,
            'New order assigned',
            'Order '.$orderNumber.' has been assigned to you.',
            [
                'order_number' => $orderNumber,
                'fulfillment_id' => $fulfillment->id,
            ],
            $actor,
        );

        DriverOrdersChanged::dispatch($driver->id, $orderNumber, DriverOrdersChanged::REASON_ASSIGNED);
    }

    private function advanceAfterAssign(OrderFulfillment $fulfillment, ?User $actor, ?FulfillmentStatus $advance): void
    {
        $steps = [FulfillmentStatus::Assigned];

        if ($advance === FulfillmentStatus::PickedUp || $advance === FulfillmentStatus::OutForDelivery) {
            $steps[] = FulfillmentStatus::PickedUp;
        }

        if ($advance === FulfillmentStatus::OutForDelivery) {
            $steps[] = FulfillmentStatus::OutForDelivery;
        }

        foreach ($steps as $step) {
            if ($fulfillment->status === $step) {
                continue;
            }

            if (
                $step === FulfillmentStatus::PickedUp
                && $fulfillment->hasUnresolvedLateItems()
            ) {
                throw ValidationException::withMessages([
                    'advance' => DriverDeliveryService::LATE_ITEMS_BLOCK_PICKUP_MESSAGE,
                ]);
            }

            $comment = match ($step) {
                FulfillmentStatus::Assigned => 'Driver assigned',
                FulfillmentStatus::PickedUp => 'Picked up',
                FulfillmentStatus::OutForDelivery => 'On the way',
                default => 'Status updated',
            };

            $fulfillment = $this->statuses->apply($fulfillment, $step, $actor, $comment);
        }
    }
}
