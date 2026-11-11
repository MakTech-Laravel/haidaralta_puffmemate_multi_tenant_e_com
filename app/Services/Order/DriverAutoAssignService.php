<?php

namespace App\Services\Order;

use App\Contracts\DistanceCalculator;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderSource;
use App\Models\OrderFulfillment;
use App\Models\User;
use App\Services\Driver\DriverLocationService;
use Throwable;

/**
 * Picks a branch driver for a customer-checkout order without touching its status.
 *
 * The driver is only reserved (`reserved_driver_id`) while the order is being
 * confirmed and packed, so driver portals and every `driver_id` consumer behave
 * exactly as before. When staff mark the order Ready the reservation is promoted
 * through the normal assignment flow. When nobody can be picked, the branch order
 * staff are notified and assign by hand.
 */
class DriverAutoAssignService
{
    public const PROMOTION_REASON = 'Auto-assigned';

    public function __construct(
        private DriverAssignmentService $drivers,
        private DriverLocationService $locations,
        private DistanceCalculator $distances,
        private BranchOrderNotifier $branchNotifier,
    ) {}

    /**
     * Reserve a driver for a freshly placed order. Idempotent: does nothing when the
     * order already has a driver or a reservation.
     */
    public function reserve(OrderFulfillment $fulfillment): ?User
    {
        $fulfillment->refresh()->loadMissing('order');

        if (! $this->isAutoAssignable($fulfillment)
            || $fulfillment->status !== FulfillmentStatus::Placed
            || $fulfillment->driver_id !== null
            || $fulfillment->reserved_driver_id !== null) {
            return null;
        }

        $driver = $this->pickDriver($fulfillment);

        if ($driver === null) {
            $this->notifyBranchDriverNeeded($fulfillment);

            return null;
        }

        $fulfillment->forceFill(['reserved_driver_id' => $driver->id])->save();

        return $driver;
    }

    /**
     * Turn the reservation into a real assignment once the order is Ready. Never
     * throws: marking an order Ready must succeed even when no driver can be set.
     */
    public function promoteAtReady(OrderFulfillment $fulfillment, ?User $actor = null): void
    {
        try {
            $fulfillment->refresh()->loadMissing('order');

            if (! $this->isAutoAssignable($fulfillment) || $fulfillment->status !== FulfillmentStatus::Ready) {
                return;
            }

            if ($fulfillment->driver_id !== null) {
                $this->clearReservation($fulfillment);

                return;
            }

            $driver = $this->reservedDriverIfEligible($fulfillment) ?? $this->pickDriver($fulfillment);

            if ($driver === null) {
                $this->leaveForManualAssignment($fulfillment);

                return;
            }

            $this->drivers->assign($fulfillment, $driver, $actor, self::PROMOTION_REASON);
            $this->clearReservation($fulfillment);
        } catch (Throwable $exception) {
            report($exception);

            $fulfillment->refresh();

            if ($fulfillment->driver_id === null) {
                $this->leaveForManualAssignment($fulfillment);
            } else {
                $this->clearReservation($fulfillment);
            }
        }
    }

    /**
     * Eligible branch driver with the fewest open orders (reserved ones included);
     * ties go to the driver whose live position is closest to the customer. A driver
     * with no recent position sorts last but is not excluded.
     */
    public function pickDriver(OrderFulfillment $fulfillment): ?User
    {
        $order = $fulfillment->order;
        $latitude = $order?->latitude !== null ? (float) $order->latitude : null;
        $longitude = $order?->longitude !== null ? (float) $order->longitude : null;
        $workload = $this->drivers->workload();

        return $this->drivers->candidates($fulfillment)
            ->sortBy(fn (User $driver): array => [
                (int) ($workload[$driver->id] ?? 0),
                $this->distanceToCustomer($driver, $latitude, $longitude),
                $driver->id,
            ])
            ->first();
    }

    /**
     * Only customer-checkout orders are auto-assigned. Driver sales are delivered
     * immediately and wholesale orders are placed by an administrator.
     */
    private function isAutoAssignable(OrderFulfillment $fulfillment): bool
    {
        return $fulfillment->order?->source === OrderSource::CustomerCheckout;
    }

    private function reservedDriverIfEligible(OrderFulfillment $fulfillment): ?User
    {
        if ($fulfillment->reserved_driver_id === null) {
            return null;
        }

        return $this->drivers->candidates($fulfillment)->firstWhere('id', $fulfillment->reserved_driver_id);
    }

    private function distanceToCustomer(User $driver, ?float $latitude, ?float $longitude): float
    {
        $position = $this->locations->lastPosition($driver->id);

        if ($position === null || $latitude === null || $longitude === null) {
            return PHP_FLOAT_MAX;
        }

        return $this->distances->distanceKm($latitude, $longitude, (float) $position['lat'], (float) $position['lng']);
    }

    private function clearReservation(OrderFulfillment $fulfillment): void
    {
        if ($fulfillment->reserved_driver_id !== null) {
            $fulfillment->forceFill(['reserved_driver_id' => null])->save();
        }
    }

    private function leaveForManualAssignment(OrderFulfillment $fulfillment): void
    {
        $this->clearReservation($fulfillment);
        $this->notifyBranchDriverNeeded($fulfillment);
    }

    private function notifyBranchDriverNeeded(OrderFulfillment $fulfillment): void
    {
        $orderNumber = (string) ($fulfillment->order?->order_number ?? $fulfillment->fulfillment_number);

        $this->branchNotifier->newOrder(
            $fulfillment,
            'Driver needed',
            'No driver could be picked automatically for order '.$orderNumber.'. Please assign one.',
        );
    }
}
