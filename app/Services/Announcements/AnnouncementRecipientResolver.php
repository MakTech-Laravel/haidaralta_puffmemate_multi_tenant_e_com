<?php

namespace App\Services\Announcements;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementSendMode;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AnnouncementRecipientResolver
{
    /**
     * @param  list<AnnouncementAudience>  $audiences
     * @param  list<array{id: int, audience: AnnouncementAudience}>  $recipients
     * @return list<int>
     */
    public function resolve(
        AnnouncementSendMode $mode,
        array $audiences,
        array $recipients,
    ): array {
        if ($mode === AnnouncementSendMode::Group) {
            $types = array_map(
                fn (AnnouncementAudience $audience) => $audience->userType(),
                $audiences,
            );

            return User::query()
                ->where('is_active', true)
                ->whereIn('user_type', $types)
                ->orderBy('id')
                ->pluck('id')
                ->map(fn (int|string $id): int => (int) $id)
                ->all();
        }

        $ids = [];

        foreach ($recipients as $recipient) {
            $user = User::query()
                ->whereKey($recipient['id'])
                ->where('is_active', true)
                ->first();

            if ($user === null || $user->user_type !== $recipient['audience']->userType()) {
                throw ValidationException::withMessages([
                    'recipients' => 'One or more recipients are invalid for the selected audience.',
                ]);
            }

            $ids[$user->id] = $user->id;
        }

        return array_values($ids);
    }
}
