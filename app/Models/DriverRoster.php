<?php

namespace App\Models;

use App\Enums\DriverRosterStatus;
use App\Models\Concerns\AuditsChanges;
use Carbon\CarbonInterface;
use Database\Factories\DriverRosterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverRoster extends Model
{
    /** @use HasFactory<DriverRosterFactory> */
    use AuditsChanges, HasFactory;

    /** @var list<int> */
    public const DAYS_ALL = [1, 2, 3, 4, 5, 6, 7];

    /** @var list<int> */
    public const DAYS_WEEKDAYS = [1, 2, 3, 4, 5];

    /**
     * @var array<int, string>
     */
    public const DAY_LABELS = [
        1 => 'Mon',
        2 => 'Tue',
        3 => 'Wed',
        4 => 'Thu',
        5 => 'Fri',
        6 => 'Sat',
        7 => 'Sun',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'driver_id',
        'branch_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'days_of_week',
        'status',
    ];

    /**
     * @param  list<mixed>|null  $days
     * @return list<int>
     */
    public static function normalizeDays(?array $days): array
    {
        $values = collect($days ?? [])
            ->map(fn (mixed $day): int => (int) $day)
            ->filter(fn (int $day): bool => $day >= 1 && $day <= 7)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $values === [] ? self::DAYS_ALL : $values;
    }

    /**
     * @param  list<int>  $days
     */
    public static function daysLabel(array $days): string
    {
        $normalized = self::normalizeDays($days);

        if ($normalized === self::DAYS_ALL) {
            return 'All days';
        }

        if ($normalized === self::DAYS_WEEKDAYS) {
            return 'Mon–Fri';
        }

        if ($normalized === [6, 7]) {
            return 'Sat–Sun';
        }

        return collect($normalized)
            ->map(fn (int $day): string => self::DAY_LABELS[$day])
            ->implode(', ');
    }

    /**
     * @return list<int>
     */
    public function normalizedDaysOfWeek(): array
    {
        /** @var list<mixed>|null $days */
        $days = $this->days_of_week;

        return self::normalizeDays(is_array($days) ? $days : null);
    }

    public function appliesOn(CarbonInterface $date): bool
    {
        $day = $date->toDateString();
        $start = $this->start_date?->format('Y-m-d');
        $end = $this->end_date?->format('Y-m-d');

        if ($start === null || $end === null || $day < $start || $day > $end) {
            return false;
        }

        return in_array($date->isoWeekday(), $this->normalizedDaysOfWeek(), true);
    }

    public function coversTime(string $time): bool
    {
        $start = $this->start_time?->format('H:i:s');
        $end = $this->end_time?->format('H:i:s');
        $normalized = strlen($time) === 5 ? "{$time}:00" : $time;

        return $start !== null && $end !== null && $start <= $normalized && $end > $normalized;
    }

    public function coversMoment(CarbonInterface $moment): bool
    {
        return $this->status === DriverRosterStatus::Scheduled
            && $this->appliesOn($moment)
            && $this->coversTime($moment->format('H:i:s'));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'days_of_week' => 'array',
            'status' => DriverRosterStatus::class,
        ];
    }
}
