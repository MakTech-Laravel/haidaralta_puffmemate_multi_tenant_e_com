<?php

namespace App\Support;

use App\Enums\FulfillmentStatus;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Collection;

final class OrderStatusTimeline
{
    /**
     * @param  iterable<int, OrderStatusHistory>  $histories
     * @return list<array{label: string, fromLabel: string|null, actorName: string, detail: string, comment: string|null}>
     */
    public static function rows(iterable $histories): array
    {
        return Collection::make($histories)
            ->map(function (OrderStatusHistory $row): array {
                $next = FulfillmentStatus::tryFrom((string) $row->new_status);
                $previous = $row->old_status !== null
                    ? FulfillmentStatus::tryFrom((string) $row->old_status)
                    : null;

                return [
                    'label' => $next?->label() ?? (string) $row->new_status,
                    'fromLabel' => $previous?->label(),
                    'actorName' => $row->changer?->name ?? 'System',
                    'detail' => DateTimeFormat::dateTime($row->created_at) ?? '',
                    'comment' => $row->comment,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Every status on this order's path, including ones that have not happened yet.
     *
     * @param  iterable<int, OrderStatusHistory>  $histories
     * @return list<array{key: string, label: string, state: string, actorName: string|null, detail: string|null, comment: string|null}>
     */
    public static function steps(FulfillmentStatus $current, iterable $histories, bool $customer = false): array
    {
        $history = Collection::make($histories);
        $events = [];

        foreach ($history as $row) {
            $key = (string) $row->new_status;

            if (! isset($events[$key]) || $row->fulfillment_id !== null) {
                $events[$key] = $row;
            }
        }

        $track = $customer
            ? self::customerTrack($current, $history)
            : self::track($current, $history);
        $displayCurrent = $customer
            ? self::customerMilestoneFor($current)
            : $current;
        $currentIndex = array_search($displayCurrent, $track, true);

        if ($currentIndex === false) {
            $track[] = $displayCurrent;
            $currentIndex = count($track) - 1;
        }

        $steps = [];

        foreach ($track as $index => $status) {
            $row = $customer
                ? self::customerEventForMilestone($status, $events)
                : ($events[$status->value] ?? null);
            $happened = $row !== null;
            $state = match (true) {
                $index === $currentIndex => 'current',
                $index < $currentIndex && ($customer || $happened) => 'completed',
                default => 'upcoming',
            };

            $steps[] = [
                'key' => $status->value,
                'label' => $customer ? $status->customerLabel() : $status->label(),
                'state' => $state,
                'actorName' => $state === 'upcoming' ? null : $row?->changer?->name,
                'detail' => $state === 'upcoming' ? null : DateTimeFormat::dateTime($row?->created_at),
                'comment' => $state === 'upcoming' ? null : $row?->comment,
            ];
        }

        return $steps;
    }

    /**
     * @param  Collection<int, OrderStatusHistory>  $histories
     * @return list<FulfillmentStatus>
     */
    private static function track(FulfillmentStatus $current, Collection $histories): array
    {
        $forward = [
            FulfillmentStatus::Pending,
            FulfillmentStatus::Placed,
            FulfillmentStatus::Processing,
            FulfillmentStatus::Ready,
            FulfillmentStatus::Assigned,
            FulfillmentStatus::PickedUp,
            FulfillmentStatus::OutForDelivery,
            FulfillmentStatus::Arrived,
            FulfillmentStatus::Delivered,
        ];

        if ($current === FulfillmentStatus::Hold) {
            array_splice($forward, 1, 0, [FulfillmentStatus::Hold]);

            return $forward;
        }

        if (in_array($current, [FulfillmentStatus::DeliveryFailed, FulfillmentStatus::Returning, FulfillmentStatus::Returned], true)) {
            $head = array_slice($forward, 0, array_search(FulfillmentStatus::Arrived, $forward, true) + 1);
            $tail = match ($current) {
                FulfillmentStatus::DeliveryFailed => [FulfillmentStatus::DeliveryFailed],
                FulfillmentStatus::Returning => [FulfillmentStatus::DeliveryFailed, FulfillmentStatus::Returning],
                default => [FulfillmentStatus::DeliveryFailed, FulfillmentStatus::Returning, FulfillmentStatus::Returned],
            };

            return [...$head, ...$tail];
        }

        if ($current === FulfillmentStatus::Cancelled) {
            $last = null;

            foreach ($histories as $row) {
                $status = FulfillmentStatus::tryFrom((string) $row->new_status);

                if ($status !== null && $status !== FulfillmentStatus::Cancelled) {
                    $last = $status;
                }
            }

            $index = $last === null ? false : array_search($last, $forward, true);
            $prefix = $index === false ? [] : array_slice($forward, 0, $index + 1);

            return [...$prefix, FulfillmentStatus::Cancelled];
        }

        return $forward;
    }

    /**
     * @param  Collection<int, OrderStatusHistory>  $histories
     * @return list<FulfillmentStatus>
     */
    private static function customerTrack(FulfillmentStatus $current, Collection $histories): array
    {
        $forward = [
            FulfillmentStatus::Placed,
            FulfillmentStatus::Ready,
            FulfillmentStatus::OutForDelivery,
            FulfillmentStatus::Arrived,
            FulfillmentStatus::Delivered,
        ];

        if ($current === FulfillmentStatus::Hold) {
            array_splice($forward, 1, 0, [FulfillmentStatus::Hold]);

            return $forward;
        }

        if (in_array($current, [FulfillmentStatus::DeliveryFailed, FulfillmentStatus::Returning, FulfillmentStatus::Returned], true)) {
            $head = array_slice($forward, 0, array_search(FulfillmentStatus::Arrived, $forward, true) + 1);
            $tail = match ($current) {
                FulfillmentStatus::DeliveryFailed => [FulfillmentStatus::DeliveryFailed],
                FulfillmentStatus::Returning => [FulfillmentStatus::DeliveryFailed, FulfillmentStatus::Returning],
                default => [FulfillmentStatus::DeliveryFailed, FulfillmentStatus::Returning, FulfillmentStatus::Returned],
            };

            return [...$head, ...$tail];
        }

        if ($current === FulfillmentStatus::Cancelled) {
            $lastMilestone = null;

            foreach ($histories as $row) {
                $status = FulfillmentStatus::tryFrom((string) $row->new_status);

                if ($status === null || $status === FulfillmentStatus::Cancelled) {
                    continue;
                }

                $lastMilestone = self::customerMilestoneFor($status);
            }

            $index = $lastMilestone === null ? false : array_search($lastMilestone, $forward, true);
            $prefix = $index === false ? [] : array_slice($forward, 0, $index + 1);

            return [...$prefix, FulfillmentStatus::Cancelled];
        }

        return $forward;
    }

    private static function customerMilestoneFor(FulfillmentStatus $status): FulfillmentStatus
    {
        return match ($status) {
            FulfillmentStatus::Pending, FulfillmentStatus::Placed => FulfillmentStatus::Placed,
            FulfillmentStatus::Hold => FulfillmentStatus::Hold,
            FulfillmentStatus::Processing, FulfillmentStatus::Ready => FulfillmentStatus::Ready,
            FulfillmentStatus::Assigned, FulfillmentStatus::PickedUp, FulfillmentStatus::OutForDelivery => FulfillmentStatus::OutForDelivery,
            FulfillmentStatus::Arrived => FulfillmentStatus::Arrived,
            FulfillmentStatus::Delivered => FulfillmentStatus::Delivered,
            FulfillmentStatus::DeliveryFailed => FulfillmentStatus::DeliveryFailed,
            FulfillmentStatus::Returning => FulfillmentStatus::Returning,
            FulfillmentStatus::Returned => FulfillmentStatus::Returned,
            FulfillmentStatus::Cancelled => FulfillmentStatus::Cancelled,
        };
    }

    /**
     * @param  array<string, OrderStatusHistory>  $events
     */
    private static function customerEventForMilestone(FulfillmentStatus $milestone, array $events): ?OrderStatusHistory
    {
        foreach (self::customerMilestoneSources($milestone) as $status) {
            if (isset($events[$status->value])) {
                return $events[$status->value];
            }
        }

        return null;
    }

    /**
     * @return list<FulfillmentStatus>
     */
    private static function customerMilestoneSources(FulfillmentStatus $milestone): array
    {
        return match ($milestone) {
            FulfillmentStatus::Placed => [FulfillmentStatus::Placed, FulfillmentStatus::Pending],
            FulfillmentStatus::Ready => [FulfillmentStatus::Ready, FulfillmentStatus::Processing],
            FulfillmentStatus::OutForDelivery => [
                FulfillmentStatus::OutForDelivery,
                FulfillmentStatus::PickedUp,
                FulfillmentStatus::Assigned,
            ],
            default => [$milestone],
        };
    }
}
