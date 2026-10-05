<?php

namespace App\Services\Order;

use App\Contracts\DistanceCalculator;
use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverAvailabilityStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\UserType;
use App\Models\OrderDriverAssignmentHistory;
use App\Models\OrderFulfillment;
use App\Models\User;
use App\Services\Conversation\ConversationService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DriverAssignmentService
{
    public function __construct(
        private FulfillmentStatusService $statuses,
        private ConversationService $conversations,
        private DistanceCalculator $distances,
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
        return $fulfillment->status !== FulfillmentStatus::Delivered;
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

    public function assign(OrderFulfillment $fulfillment, User $driver, User $actor, ?string $reason = null): OrderFulfillment
    {
        if ($fulfillment->status === FulfillmentStatus::Delivered) {
            throw ValidationException::withMessages([
                'driver_id' => 'Cannot assign a driver after the order is delivered.',
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
                'reason' => 'Provide a reason when changing the assigned driver.',
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

        if (in_array($fulfillment->status, [
            FulfillmentStatus::Pending,
            FulfillmentStatus::Processing,
            FulfillmentStatus::Ready,
        ], true)) {
            $this->statuses->apply($fulfillment, FulfillmentStatus::Assigned, $actor, 'Driver assigned');
        }

        $this->conversations->ensureDriver($fulfillment->order()->first(), $driver);

        return $fulfillment->refresh();
    }
}
