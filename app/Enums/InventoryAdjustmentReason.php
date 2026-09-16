<?php

namespace App\Enums;

enum InventoryAdjustmentReason: string
{
    case CountCorrection = 'count_correction';
    case Damaged = 'damaged';
    case Expired = 'expired';
    case Shrinkage = 'shrinkage';
    case Found = 'found';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CountCorrection => 'Count correction',
            self::Damaged => 'Damaged',
            self::Expired => 'Expired',
            self::Shrinkage => 'Shrinkage / missing',
            self::Found => 'Found stock',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $reason): array => [
                'value' => $reason->value,
                'label' => $reason->label(),
            ],
            self::cases(),
        );
    }

    public function requiresNote(): bool
    {
        return $this === self::Other;
    }

    public function displayLabel(?string $note = null): string
    {
        if ($this === self::Other && is_string($note) && trim($note) !== '') {
            return trim($note);
        }

        return $this->label();
    }

    public static function fromNullable(mixed $value): ?self
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return self::from($value);
    }

    public static function noteFrom(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $note = trim($value);

        return $note === '' ? null : $note;
    }
}
