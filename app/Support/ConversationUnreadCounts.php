<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Per-user unread tallies for the conversation inbox.
 *
 * One grouped query covers every thread the user takes part in, and the result
 * is cached in Redis so scrolling the inbox does not re-aggregate the messages
 * table. Sending or reading busts the cache for the people affected.
 */
final class ConversationUnreadCounts
{
    private const TTL_SECONDS = 300;

    /**
     * Unread count keyed by conversation id.
     *
     * @return array<int, int>
     */
    public static function forUser(User $user): array
    {
        return Cache::remember(
            self::cacheKey($user->id),
            self::TTL_SECONDS,
            static fn (): array => self::query($user->id),
        );
    }

    public static function forget(int $userId): void
    {
        Cache::forget(self::cacheKey($userId));
    }

    /**
     * Bust every participant of a thread at once, e.g. after a new message.
     *
     * @param  iterable<int, int>  $userIds
     */
    public static function forgetMany(iterable $userIds): void
    {
        foreach ($userIds as $userId) {
            self::forget($userId);
        }
    }

    /**
     * Messages newer than the participant's read marker, excluding their own.
     *
     * @return array<int, int>
     */
    private static function query(int $userId): array
    {
        $rows = DB::table('conversation_participants')
            ->join('messages', 'messages.conversation_id', '=', 'conversation_participants.conversation_id')
            ->where('conversation_participants.user_id', $userId)
            ->whereRaw('messages.id > coalesce(conversation_participants.last_read_message_id, 0)')
            ->where(function ($query) use ($userId): void {
                $query->whereNull('messages.sender_id')
                    ->orWhere('messages.sender_id', '!=', $userId);
            })
            ->groupBy('conversation_participants.conversation_id')
            ->selectRaw('conversation_participants.conversation_id as conversation_id, count(messages.id) as unread')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $counts[(int) $row->conversation_id] = (int) $row->unread;
        }

        return $counts;
    }

    private static function cacheKey(int $userId): string
    {
        return 'conversations:unread:'.$userId;
    }
}
