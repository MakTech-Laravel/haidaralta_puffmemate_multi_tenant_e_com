<?php

namespace App\Support;

use App\Services\Application\ApplicationSettingsService;
use Carbon\CarbonInterface;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\Carbon;

final class DateTimeFormat
{
    public static function date(DateTimeInterface|string|null $value): ?string
    {
        $carbon = self::toDisplayCarbon($value);

        if ($carbon === null) {
            return null;
        }

        return $carbon->format(app(ApplicationSettingsService::class)->current()->date_format);
    }

    public static function time(DateTimeInterface|string|null $value): ?string
    {
        $carbon = self::toDisplayCarbon($value);

        if ($carbon === null) {
            return null;
        }

        return $carbon->format(app(ApplicationSettingsService::class)->current()->time_format);
    }

    public static function dateTime(DateTimeInterface|string|null $value): ?string
    {
        $carbon = self::toDisplayCarbon($value);

        if ($carbon === null) {
            return null;
        }

        $settings = app(ApplicationSettingsService::class)->current();

        return $carbon->format($settings->date_format.' '.$settings->time_format);
    }

    public static function displayTimezone(): string
    {
        return app(ApplicationSettingsService::class)->current()->display_timezone;
    }

    public static function now(): Carbon
    {
        return Carbon::now(new DateTimeZone(self::displayTimezone()));
    }

    public static function at(?CarbonInterface $moment = null): Carbon
    {
        $carbon = $moment === null
            ? self::now()
            : Carbon::instance($moment);

        return $carbon->copy()->setTimezone(new DateTimeZone(self::displayTimezone()));
    }

    private static function toDisplayCarbon(DateTimeInterface|string|null $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        $carbon = $value instanceof CarbonInterface
            ? Carbon::instance($value)
            : Carbon::parse($value);

        return $carbon->setTimezone(new DateTimeZone(self::displayTimezone()));
    }
}
