<?php

namespace App\Support\Delivery;

use App\Models\DeliveryShift;
use App\Support\DutyWindow;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class DeliveryShiftAvailability
{
    public const METHOD_SAME_DAY = 'regular';

    public const METHOD_LATE = 'express';

    public function now(): CarbonInterface
    {
        return now(config('app.timezone'));
    }

    public function lateDeliveryMaxDays(): int
    {
        return max(1, (int) config('catalog.late_delivery_max_days', 14));
    }

    /**
     * @return Collection<int, DeliveryShift>
     */
    public function activeShifts(): Collection
    {
        return DeliveryShift::query()
            ->where('is_active', true)
            ->orderBy('start_time')
            ->orderBy('name')
            ->get();
    }

    /**
     * Same-day bookable shifts: start has not passed (exact start allowed) and window not ended.
     *
     * @return Collection<int, DeliveryShift>
     */
    public function eligibleForSameDay(?CarbonInterface $now = null): Collection
    {
        $moment = $now ?? $this->now();
        $current = DutyWindow::normalizeTime($moment->format('H:i:s'));

        return $this->activeShifts()
            ->filter(function (DeliveryShift $shift) use ($current): bool {
                $start = DutyWindow::normalizeTime($shift->start_time->format('H:i:s'));
                $end = DutyWindow::normalizeTime($shift->end_time->format('H:i:s'));

                return $start >= $current && $end > $current;
            })
            ->values();
    }

    public function lateMinDate(?CarbonInterface $now = null): string
    {
        return ($now ?? $this->now())->copy()->startOfDay()->addDay()->toDateString();
    }

    public function lateMaxDate(?CarbonInterface $now = null): string
    {
        return ($now ?? $this->now())->copy()->startOfDay()->addDays($this->lateDeliveryMaxDays())->toDateString();
    }

    /**
     * @return array{0: DeliveryShift, 1: string, 2: string} Shift, display label, delivery_date Y-m-d
     */
    public function assertSelectable(
        string $methodKey,
        mixed $deliveryDate,
        mixed $shiftId,
        ?CarbonInterface $now = null,
    ): array {
        $moment = $now ?? $this->now();

        if ($shiftId === null || $shiftId === '') {
            throw ValidationException::withMessages([
                'time_slot_id' => 'Choose a delivery shift.',
            ]);
        }

        $shift = DeliveryShift::query()
            ->whereKey($shiftId)
            ->where('is_active', true)
            ->first();

        if ($shift === null) {
            throw ValidationException::withMessages([
                'time_slot_id' => 'Choose a delivery shift.',
            ]);
        }

        if ($methodKey === self::METHOD_SAME_DAY) {
            $eligible = $this->eligibleForSameDay($moment);

            if ($eligible->where('id', $shift->id)->isEmpty()) {
                throw ValidationException::withMessages([
                    'time_slot_id' => 'That delivery shift is no longer available for same-day delivery.',
                ]);
            }

            return [$shift, $this->label($shift), $moment->toDateString()];
        }

        if ($methodKey === self::METHOD_LATE) {
            if (! is_string($deliveryDate) || $deliveryDate === '') {
                throw ValidationException::withMessages([
                    'delivery_date' => 'Choose a delivery date.',
                ]);
            }

            try {
                $date = Carbon::parse($deliveryDate, config('app.timezone'))->startOfDay();
            } catch (\Throwable) {
                throw ValidationException::withMessages([
                    'delivery_date' => 'Choose a delivery date.',
                ]);
            }

            $min = Carbon::parse($this->lateMinDate($moment), config('app.timezone'))->startOfDay();
            $max = Carbon::parse($this->lateMaxDate($moment), config('app.timezone'))->startOfDay();

            if ($date->lt($min) || $date->gt($max)) {
                throw ValidationException::withMessages([
                    'delivery_date' => 'Choose a delivery date between tomorrow and the next '.$this->lateDeliveryMaxDays().' days.',
                ]);
            }

            return [$shift, $this->label($shift), $date->toDateString()];
        }

        throw ValidationException::withMessages([
            'delivery_method' => 'Choose a delivery method.',
        ]);
    }

    public function label(DeliveryShift $shift): string
    {
        return sprintf(
            '%s (%s – %s)',
            $shift->name,
            $shift->start_time->format('g:i A'),
            $shift->end_time->format('g:i A'),
        );
    }

    /**
     * @return array{id: string, label: string, start_time: string, end_time: string, name: string}
     */
    public function payloadSlot(DeliveryShift $shift): array
    {
        return [
            'id' => (string) $shift->id,
            'name' => $shift->name,
            'label' => $this->label($shift),
            'start_time' => $shift->start_time->format('H:i'),
            'end_time' => $shift->end_time->format('H:i'),
        ];
    }
}
