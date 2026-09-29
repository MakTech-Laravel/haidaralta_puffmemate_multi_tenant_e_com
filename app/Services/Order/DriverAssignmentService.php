<?php

namespace App\Services\Order;

use App\Contracts\DistanceCalculator;
use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverAvailabilityStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\UserType;
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
            ->with('driverProfile')
            ->get()
            ->sortBy(function (User $driver) use ($fulfillment, $workload): array {
                $available = $driver->driverProfile?->availability_status === DriverAvailabilityStatus::Available ? 0 : 1;
                $load = (int) ($workload[$driver->id] ?? 0);

                return [$available, $load, $this->distanceFromBranch($fulfillment, $driver), $driver->id];
            })
            ->values();
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

    public function assign(OrderFulfillment $fulfillment, User $driver, User $actor): OrderFulfillment
    {
        if (! in_array($fulfillment->status, [FulfillmentStatus::Ready, FulfillmentStatus::Assigned], true)) {
            throw ValidationException::withMessages([
                'driver_id' => 'Assign a driver after the order is packed.',
            ]);
        }

        if ($fulfillment->status === FulfillmentStatus::Assigned && $fulfillment->driver_id === $driver->id) {
            return $fulfillment;
        }

        $allowed = $this->candidates($fulfillment)->pluck('id');

        if (! $allowed->contains($driver->id)) {
            throw ValidationException::withMessages([
                'driver_id' => 'Choose an active driver from this branch.',
            ]);
        }

        $fulfillment->forceFill(['driver_id' => $driver->id])->save();

        if ($fulfillment->status === FulfillmentStatus::Ready) {
            $this->statuses->apply($fulfillment, FulfillmentStatus::Assigned, $actor, 'Driver assigned');
        }

        $this->conversations->ensureDriver($fulfillment->order()->first(), $driver);

        return $fulfillment->refresh();
    }
}
