<?php

namespace App\Services\Order;

use App\Contracts\DistanceCalculator;
use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverAvailabilityStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\NotificationType;
use App\Enums\UserType;
use App\Events\DriverOrdersChanged;
use App\Models\OrderDriverAssignmentHistory;
use App\Models\OrderFulfillment;
use App\Models\User;
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
        $workload = OrderFulfillment::query()
            ->whereIn('status', [
                FulfillmentStatus::Assigned->value,
                FulfillmentStatus::PickedUp->value,
                FulfillmentStatus::OutForDelivery->value,
                FulfillmentStatus::Arrived->value,
                FulfillmentStatus::DeliveryFailed->value,
                FulfillmentStatus::Returning->value,
            ])
            ->get()
            ->countBy('driver_id');

        return User::query()
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
            ->get()
            ->filter(fn (User $driver): bool => DriverAvailability::effective($driver) === DriverAvailabilityStatus::Available)
            ->sortBy(function (User $driver) use ($fulfillment, $workload): array {
                $available = $driver->driverProfile?->availability_status === DriverAvailabilityStatus::Available ? 0 : 1;
                $load = (int) ($workload[$driver->id] ?? 0);

                return [$available, $load, $this->distanceFromBranch($fulfillment, $driver), $driver->id];
            })
            ->values();
    }

    /**
     * Whether staff may assign or reassign a driver for this fulfillment.
     */
    public function canAssign(OrderFulfillment $fulfillment): bool
    {
        if ($fulfillment->driver_id !== null) {
            return false;
        }

        return in_array($fulfillment->status, [
            FulfillmentStatus::Placed,
            FulfillmentStatus::Processing,
            FulfillmentStatus::Ready,
            FulfillmentStatus::Returned,
        ], true);
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
        User $actor,
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

        if (($fromDriverId !== null || $fulfillment->status === FulfillmentStatus::Returned) && $reason === null) {
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

        $fulfillment->forceFill(['driver_id' => $driver->id])->save();

        OrderDriverAssignmentHistory::query()->create([
            'order_id' => $fulfillment->order_id,
            'fulfillment_id' => $fulfillment->id,
            'from_driver_id' => $fromDriverId,
            'to_driver_id' => $driver->id,
            'reason' => $reason,
            'changed_by' => $actor->id,
        ]);

        $this->advanceAfterAssign($fulfillment->refresh(), $actor, $advance);

        $fulfillment->refresh();
        $this->notifyDriver($fulfillment, $driver, $actor);

        return $fulfillment;
    }

    /**
     * Inbox notification plus a live refresh signal for the driver's open pages.
     */
    private function notifyDriver(OrderFulfillment $fulfillment, User $driver, User $actor): void
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

    private function advanceAfterAssign(OrderFulfillment $fulfillment, User $actor, ?FulfillmentStatus $advance): void
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
