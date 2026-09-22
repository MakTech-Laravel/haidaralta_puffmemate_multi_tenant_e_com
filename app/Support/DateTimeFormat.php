<?php

namespace App\Support;

use App\Services\Application\ApplicationSettingsService;
use Carbon\CarbonInterface;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\Carbon;
use Throwable;

final class DateTimeFormat
{
    public const VIEWER_TIMEZONE_COOKIE = 'app_timezone';

    public const VIEWER_TIMEZONE_HEADER = 'X-Timezone';

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

    /**
     * Viewer IANA timezone from the current request (cookie or header), else UTC.
     */
    public static function displayTimezone(): string
    {
        $candidates = [
            request()?->header(self::VIEWER_TIMEZONE_HEADER),
            request()?->cookie(self::VIEWER_TIMEZONE_COOKIE),
        ];

        foreach ($candidates as $candidate) {
            $timezone = self::normalizeTimezone(is_string($candidate) ? $candidate : null);

            if ($timezone !== null) {
                return $timezone;
            }
        }

        return 'UTC';
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

    private static function normalizeTimezone(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $timezone = trim($value);

        if ($timezone === '') {
            return null;
        }

        try {
            new DateTimeZone($timezone);
        } catch (Throwable) {
            return null;
        }

        return $timezone;
    }
}
