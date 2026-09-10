<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait AuditsChanges
{
    use LogsActivity;

    /**
     * Attributes that must never appear in activity logs.
     *
     * @var list<string>
     */
    protected array $auditExcept = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'token',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        $except = array_values(array_unique([
            ...$this->auditExcept,
            ...array_values($this->getHidden()),
        ]));

        $fillable = array_values(array_diff($this->getFillable(), $except));

        $options = LogOptions::defaults()->logOnlyDirty()->dontLogEmptyChanges();

        if ($fillable !== []) {
            return $options->logOnly($fillable);
        }

        return $options->logAll()->logExcept($except);
    }
}
