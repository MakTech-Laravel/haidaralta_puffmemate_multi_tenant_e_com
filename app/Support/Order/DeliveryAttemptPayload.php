<?php

namespace App\Support\Order;

use App\Enums\DeliveryFailureReason;
use App\Models\DeliveryAttempt;
use Illuminate\Support\Collection;

final class DeliveryAttemptPayload
{
    /**
     * @param  Collection<int, DeliveryAttempt>|iterable<int, DeliveryAttempt>  $attempts
     * @return list<array{id: int, reason: string, reasonLabel: string, note: string|null, attemptedAt: string|null, driverName: string|null}>
     */
    public static function list(iterable $attempts): array
    {
        return Collection::make($attempts)
            ->sortBy(fn (DeliveryAttempt $attempt): int => $attempt->id)
            ->values()
            ->map(fn (DeliveryAttempt $attempt): array => self::one($attempt))
            ->all();
    }

    /**
     * @return array{id: int, reason: string, reasonLabel: string, note: string|null, attemptedAt: string|null, driverName: string|null}
     */
    public static function one(DeliveryAttempt $attempt): array
    {
        /** @var DeliveryFailureReason $reason */
        $reason = $attempt->reason;

        return [
            'id' => $attempt->id,
            'reason' => $reason->value,
            'reasonLabel' => $reason->label(),
            'note' => $attempt->note,
            'attemptedAt' => $attempt->attempted_at?->toIso8601String(),
            'driverName' => $attempt->driver?->name,
        ];
    }
}
