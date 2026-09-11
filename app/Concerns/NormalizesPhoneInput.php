<?php

namespace App\Concerns;

use App\Support\Phone;
use Throwable;

trait NormalizesPhoneInput
{
    protected function mergeNormalizedPhone(?string $field = 'phone'): void
    {
        $raw = $this->input($field);

        if (! is_string($raw) || $raw === '') {
            return;
        }

        try {
            $this->merge([$field => Phone::toE164($raw)]);
        } catch (Throwable) {
            // Leave raw value for the phone validation rule.
        }
    }

    protected function normalizeBlankEmailToNull(): void
    {
        if ($this->input('email') === '') {
            $this->merge(['email' => null]);
        }
    }
}
