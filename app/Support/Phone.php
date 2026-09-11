<?php

namespace App\Support;

use Propaganistas\LaravelPhone\PhoneNumber;

final class Phone
{
    public const DEFAULT_REGION = 'AU';

    /**
     * Normalize a phone number to E.164 using AU as the default region.
     */
    public static function toE164(string $number, string $region = self::DEFAULT_REGION): string
    {
        return (new PhoneNumber($number, $region))->formatE164();
    }

    /**
     * @return array<int, string>
     */
    public static function validationRule(bool $mobileOnly = true): array
    {
        $parameters = ['INTERNATIONAL', self::DEFAULT_REGION];

        if ($mobileOnly) {
            $parameters[] = 'mobile';
        }

        return ['phone:'.implode(',', $parameters)];
    }
}
