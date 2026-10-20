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
     * Activity rows split at the last Returned when a new attempt has started.
     *
     * @param  iterable<int, OrderStatusHistory>  $histories
     * @return array{current: list<array{key: string, label: string, actorName: string|null, detail: string|null, comment: string|null}>, previous: list<array{key: string, label: string, actorName: string|null, detail: string|null, comment: string|null}>}
     */
    public static function activitySections(iterable $histories): array
    {
        $list = Collection::make($histories)->values();
        $splitAt = self::lastReturnedIndex($list);

        if ($splitAt === null || $splitAt >= $list->count() - 1) {
            return [
                'current' => self::activityItems($list),
                'previous' => [],
            ];
        }

        return [
            'current' => self::activityItems($list->slice($splitAt + 1)->values()),
            'previous' => self::activityItems($list->slice(0, $splitAt + 1)->values()),
        ];
    }

    /**
     * Closed first-attempt progress rail through Returned, when a re-attempt has started.
     *
     * @param  iterable<int, OrderStatusHistory>  $histories
     * @return list<array{key: string, label: string, state: string, actorName: string|null, detail: string|null, comment: string|null}>
     */
    public static function previousAttemptSteps(iterable $histories, bool $customer = false): array
    {
        $list = Collection::make($histories)->values();
        $splitAt = self::lastReturnedIndex($list);

        if ($splitAt === null || $splitAt >= $list->count() - 1) {
            return [];
        }

        $previousHistories = $list->slice(0, $splitAt + 1)->values();
        $steps = self::steps(FulfillmentStatus::Returned, $previousHistories, $customer);

        return array_map(
            static function (array $step): array {
                $step['state'] = 'completed';

                return $step;
            },
            $steps,
        );
    }

    /**
     * Every status on this order's path, including ones that have not happened yet.
     *
     * @param  iterable<int, OrderStatusHistory>  $histories
     * @return list<array{key: string, label: string, state: string, actorName: string|null, detail: string|null, comment: string|null}>
     */
    public static function steps(FulfillmentStatus $current, iterable $histories, bool $customer = false): array
    {
        $history = Collection::make($histories)->values();
        $reattempt = self::isReattemptAfterReturn($current, $history);
        $historyForEvents = $reattempt
            ? self::historiesAfterLastReturned($history)
            : $history;
        $events = [];

        foreach ($historyForEvents as $row) {
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
        $currentIndex = self::lastIndexOf($track, $displayCurrent);

        if ($currentIndex === false) {
            $track[] = $displayCurrent;
            $currentIndex = count($track) - 1;
        }

        $cancelled = $displayCurrent === FulfillmentStatus::Cancelled;
        $steps = [];

        foreach ($track as $index => $status) {
            $row = $customer
                ? self::customerEventForMilestone($status, $events)
                : ($events[$status->value] ?? null);
            $happened = $row !== null;
            $state = match (true) {
                $index === $currentIndex => 'current',
                $index < $currentIndex && ($customer || $cancelled || $happened) => 'completed',
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
            return self::cancelledTrack($forward, $histories, customer: false);
        }

        if (self::isReattemptAfterReturn($current, $histories) && in_array($current, $forward, true)) {
            return self::reattemptForwardTrack($forward, FulfillmentStatus::Assigned);
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
            return self::cancelledTrack($forward, $histories, customer: true);
        }

        $milestone = self::customerMilestoneFor($current);

        if (self::isReattemptAfterReturn($current, $histories) && in_array($milestone, $forward, true)) {
            return self::reattemptForwardTrack($forward, FulfillmentStatus::OutForDelivery);
        }

        return $forward;
    }

    /**
     * @param  list<FulfillmentStatus>  $forward
     * @return list<FulfillmentStatus>
     */
    private static function reattemptForwardTrack(array $forward, FulfillmentStatus $restartFrom): array
    {
        $restartIndex = array_search($restartFrom, $forward, true);

        return $restartIndex === false
            ? $forward
            : array_slice($forward, $restartIndex);
    }

    /**
     * @param  list<FulfillmentStatus>  $forward
     * @param  Collection<int, OrderStatusHistory>  $histories
     * @return list<FulfillmentStatus>
     */
    private static function cancelledTrack(array $forward, Collection $histories, bool $customer): array
    {
        $last = null;

        foreach ($histories as $row) {
            $status = FulfillmentStatus::tryFrom((string) $row->new_status);

            if ($status === null || $status === FulfillmentStatus::Cancelled) {
                continue;
            }

            $last = $customer ? self::customerMilestoneFor($status) : $status;
        }

        if ($last === null) {
            return [FulfillmentStatus::Cancelled];
        }

        if (in_array($last, [
            FulfillmentStatus::DeliveryFailed,
            FulfillmentStatus::Returning,
            FulfillmentStatus::Returned,
        ], true)) {
            $prefix = $customer
                ? self::customerTrack($last, $histories)
                : self::track($last, $histories);

            return [...$prefix, FulfillmentStatus::Cancelled];
        }

        if ($last === FulfillmentStatus::Hold) {
            $withHold = $forward;
            array_splice($withHold, 1, 0, [FulfillmentStatus::Hold]);
            $index = array_search(FulfillmentStatus::Hold, $withHold, true);
            $prefix = $index === false ? [] : array_slice($withHold, 0, $index + 1);

            return [...$prefix, FulfillmentStatus::Cancelled];
        }

        if (self::hasReturned($histories) && in_array($last, $forward, true)) {
            $full = $customer
                ? self::customerTrack($last, $histories)
                : self::track($last, $histories);
            $index = self::lastIndexOf($full, $last);
            $prefix = $index === false ? $full : array_slice($full, 0, $index + 1);

            return [...$prefix, FulfillmentStatus::Cancelled];
        }

        $index = array_search($last, $forward, true);
        $prefix = $index === false ? [] : array_slice($forward, 0, $index + 1);

        return [...$prefix, FulfillmentStatus::Cancelled];
    }

    /**
     * @param  Collection<int, OrderStatusHistory>  $histories
     */
    private static function isReattemptAfterReturn(FulfillmentStatus $current, Collection $histories): bool
    {
        if (! self::hasReturned($histories)) {
            return false;
        }

        return ! in_array($current, [
            FulfillmentStatus::DeliveryFailed,
            FulfillmentStatus::Returning,
            FulfillmentStatus::Returned,
            FulfillmentStatus::Cancelled,
            FulfillmentStatus::Hold,
        ], true);
    }

    /**
     * @param  Collection<int, OrderStatusHistory>  $histories
     */
    private static function hasReturned(Collection $histories): bool
    {
        return self::lastReturnedIndex($histories) !== null;
    }

    /**
     * @param  Collection<int, OrderStatusHistory>  $histories
     */
    private static function lastReturnedIndex(Collection $histories): ?int
    {
        $splitAt = null;

        foreach ($histories->values() as $index => $row) {
            if (FulfillmentStatus::tryFrom((string) $row->new_status) === FulfillmentStatus::Returned) {
                $splitAt = $index;
            }
        }

        return $splitAt;
    }

    /**
     * @param  Collection<int, OrderStatusHistory>  $histories
     * @return Collection<int, OrderStatusHistory>
     */
    private static function historiesAfterLastReturned(Collection $histories): Collection
    {
        $splitAt = self::lastReturnedIndex($histories);

        if ($splitAt === null) {
            return $histories->values();
        }

        return $histories->values()->slice($splitAt + 1)->values();
    }

    /**
     * @param  Collection<int, OrderStatusHistory>  $histories
     * @return list<array{key: string, label: string, actorName: string|null, detail: string|null, comment: string|null}>
     */
    private static function activityItems(Collection $histories): array
    {
        return $histories
            ->map(function (OrderStatusHistory $row): array {
                $status = FulfillmentStatus::tryFrom((string) $row->new_status);

                return [
                    'key' => $status?->value ?? (string) $row->new_status,
                    'label' => $status?->label() ?? (string) $row->new_status,
                    'actorName' => $row->changer?->name,
                    'detail' => DateTimeFormat::dateTime($row->created_at),
                    'comment' => $row->comment,
                ];
            })
            ->reverse()
            ->values()
            ->all();
    }

    /**
     * @param  list<FulfillmentStatus>  $track
     */
    private static function lastIndexOf(array $track, FulfillmentStatus $status): int|false
    {
        for ($index = count($track) - 1; $index >= 0; $index--) {
            if ($track[$index] === $status) {
                return $index;
            }
        }

        return false;
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
