<?php

namespace App\Support;

use App\Enums\ShiftDayKind;
use App\Models\DriverRoster;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

final class ShiftDutyCalendar
{
    public const MAX_DAYS = 366;

    /**
     * @param  list<int>  $workingDays
     * @param  list<array{date?: mixed, kind?: mixed, shift_template_id?: mixed}>  $exceptions
     * @return list<array{duty_on: string, shift_template_id: int|null, kind: string}>
     */
    public static function expand(
        string $startDate,
        string $endDate,
        int $defaultTemplateId,
        array $workingDays,
        array $exceptions = [],
    ): array {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($end->lt($start)) {
            throw new InvalidArgumentException('Assignment end date must be on or after the start date.');
        }

        $span = (int) $start->diffInDays($end) + 1;

        if ($span > self::MAX_DAYS) {
            throw new InvalidArgumentException('A duty period cannot be longer than 366 days.');
        }

        $days = DriverRoster::normalizeDays($workingDays);
        $overrides = self::indexExceptions($exceptions, $start, $end);
        $rows = [];

        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $date = $cursor->toDateString();
            $exception = $overrides[$date] ?? null;

            if ($exception !== null) {
                $rows[] = $exception;

                continue;
            }

            $isWorking = in_array($cursor->isoWeekday(), $days, true);

            $rows[] = [
                'duty_on' => $date,
                'shift_template_id' => $isWorking ? $defaultTemplateId : null,
                'kind' => $isWorking
                    ? ShiftDayKind::Scheduled->value
                    : ShiftDayKind::WeeklyOff->value,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{date?: mixed, kind?: mixed, shift_template_id?: mixed}>  $exceptions
     * @return array<string, array{duty_on: string, shift_template_id: int|null, kind: string}>
     */
    private static function indexExceptions(array $exceptions, Carbon $start, Carbon $end): array
    {
        $indexed = [];

        foreach ($exceptions as $exception) {
            $date = isset($exception['date']) ? Carbon::parse((string) $exception['date'])->toDateString() : null;

            if ($date === null || $date < $start->toDateString() || $date > $end->toDateString()) {
                throw new InvalidArgumentException('A day change must fall inside the duty period.');
            }

            $kind = ShiftDayKind::tryFrom((string) ($exception['kind'] ?? ''));

            if ($kind === null) {
                throw new InvalidArgumentException('Unknown day change.');
            }

            $templateId = isset($exception['shift_template_id']) && $exception['shift_template_id'] !== null && $exception['shift_template_id'] !== ''
                ? (int) $exception['shift_template_id']
                : null;

            if ($kind === ShiftDayKind::Override && $templateId === null) {
                throw new InvalidArgumentException('Choose a shift for that day.');
            }

            if ($kind === ShiftDayKind::Scheduled && $templateId === null) {
                throw new InvalidArgumentException('A scheduled day needs a shift.');
            }

            if ($kind === ShiftDayKind::Leave || $kind === ShiftDayKind::WeeklyOff) {
                $templateId = null;
            }

            $indexed[$date] = [
                'duty_on' => $date,
                'shift_template_id' => $templateId,
                'kind' => $kind->value,
            ];
        }

        return $indexed;
    }
}
