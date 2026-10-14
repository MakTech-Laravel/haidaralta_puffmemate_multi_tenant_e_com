<?php

namespace App\Models;

use App\Enums\OtpPurpose;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $phone
 * @property OtpPurpose $purpose
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string $code_hash
 * @property string|null $code_encrypted
 * @property Carbon|null $expires_at
 * @property int $attempts
 * @property Carbon|null $consumed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class OtpRecord extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'phone',
        'purpose',
        'subject_type',
        'subject_id',
        'code_hash',
        'code_encrypted',
        'expires_at',
        'attempts',
        'consumed_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'code_hash',
        'code_encrypted',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'code_encrypted' => 'encrypted',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }
}
