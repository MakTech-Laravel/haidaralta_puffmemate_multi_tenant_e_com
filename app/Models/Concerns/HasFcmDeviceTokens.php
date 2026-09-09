<?php

namespace App\Models\Concerns;

use App\Models\FcmDeviceToken;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasFcmDeviceTokens
{
    /**
     * @return HasMany<FcmDeviceToken, $this>
     */
    public function fcmDeviceTokens(): HasMany
    {
        return $this->hasMany(FcmDeviceToken::class);
    }

    /**
     * @return list<string>
     */
    public function routeFcmNotificationTokens(): array
    {
        return $this->fcmDeviceTokens()
            ->pluck('token')
            ->filter(fn (mixed $token): bool => is_string($token) && $token !== '')
            ->values()
            ->all();
    }
}
