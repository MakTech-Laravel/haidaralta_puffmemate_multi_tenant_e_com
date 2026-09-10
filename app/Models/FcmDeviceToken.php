<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use Database\Factories\FcmDeviceTokenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property string|null $device_name
 * @property string|null $user_agent
 * @property string $platform
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable(['user_id', 'token', 'device_name', 'user_agent', 'platform', 'last_used_at'])]
class FcmDeviceToken extends Model
{
    /** @use HasFactory<FcmDeviceTokenFactory> */
    use AuditsChanges, HasFactory;

    /**
     * Never log the FCM token itself — only non-secret metadata.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'device_name', 'platform', 'last_used_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }
}
