<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

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

    /**
     * True when the term is digits / phone punctuation, not a name or email.
     */
    public static function looksLikePhoneSearch(string $term): bool
    {
        $trimmed = trim($term);

        if ($trimmed === '' || preg_match('/[a-zA-Z@]/', $trimmed) === 1) {
            return false;
        }

        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';

        return strlen($digits) >= 2 && preg_match('/^[\d\s+\-().]+$/', $trimmed) === 1;
    }

    /**
     * Local, trunk-stripped, and E.164 forms so LIKE matches stored +CC numbers.
     *
     * @return list<string>
     */
    public static function searchVariants(string $term, ?string $region = null): array
    {
        $region ??= self::DEFAULT_REGION;
        $trimmed = trim($term);

        if ($trimmed === '') {
            return [];
        }

        $variants = [$trimmed];
        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';

        if ($digits !== '') {
            $variants[] = $digits;
            self::pushTrunkStripped($variants, $digits);
        }

        $candidates = [$trimmed];

        if ($digits !== '' && ! str_starts_with($trimmed, '+')) {
            $candidates[] = '+'.$digits;
        }

        foreach ($candidates as $candidate) {
            try {
                $phone = new PhoneNumber($candidate, $region);
                $e164 = $phone->formatE164();
                $variants[] = $e164;
                $variants[] = ltrim($e164, '+');

                $nationalDigits = preg_replace('/\D+/', '', $phone->formatNational()) ?? '';

                if ($nationalDigits !== '') {
                    $variants[] = $nationalDigits;
                    self::pushTrunkStripped($variants, $nationalDigits);
                }
            } catch (Throwable) {
                // Partial or invalid input still uses raw and trunk-stripped digits.
            }
        }

        return array_values(array_unique(array_filter(
            $variants,
            fn (string $variant): bool => $variant !== '',
        )));
    }

    /**
     * OR the phone column against every search variant. Call inside a grouped where().
     *
     * @param  EloquentBuilder<*>|QueryBuilder  $query
     */
    public static function constrainLike(EloquentBuilder|QueryBuilder $query, string $column, string $term): void
    {
        foreach (self::searchVariants($term) as $variant) {
            $query->orWhere($column, 'like', '%'.$variant.'%');
        }
    }

    /**
     * @param  list<string>  $variants
     */
    private static function pushTrunkStripped(array &$variants, string $digits): void
    {
        if (! str_starts_with($digits, '0')) {
            return;
        }

        $withoutTrunk = ltrim($digits, '0');

        if ($withoutTrunk !== '') {
            $variants[] = $withoutTrunk;
        }
    }
}
