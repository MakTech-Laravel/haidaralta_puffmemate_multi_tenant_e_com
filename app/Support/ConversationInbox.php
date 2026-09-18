<?php

namespace App\Support;

use App\Enums\ConversationStatus;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * The left-hand conversation list.
 *
 * This is a messenger inbox, not a report, so it does not use the Spatie
 * DataTable contract: rows are avatar + preview + unread badge, paging is
 * infinite scroll keyed on `last_message_at`, and free text runs through
 * Meilisearch. See .ai/rules/data-tables.md "Intentional exceptions".
 */
final class ConversationInbox
{
    public const PAGE_SIZE = 25;

    public function __construct(
        private Request $request,
        private User $viewer,
        private ?int $scopedBranchId = null,
    ) {}

    /**
     * @return array{
     *     items: list<array<string, mixed>>,
     *     hasMore: bool,
     *     nextCursor: ?string,
     *     filters: array{search: string, status: string},
     *     statuses: list<array{value: string, label: string}>
     * }
     */
    public function toArray(): array
    {
        $search = trim((string) $this->request->string('search'));
        $status = (string) $this->request->string('status', 'all');
        $cursor = trim((string) $this->request->string('cursor'));

        $query = Conversation::query()
            ->with([
                'order:id,order_number,status',
                'branch:id,name,slug',
                'participants.user:id,name,avatar',
            ])
            ->when(
                $this->scopedBranchId !== null,
                fn (Builder $builder) => $builder->where('branch_id', $this->scopedBranchId),
            )
            ->when(
                ConversationStatus::tryFrom($status) !== null,
                fn (Builder $builder) => $builder->where('status', $status),
            );

        $this->applySearch($query, $search);
        $this->applyCursor($query, $cursor);

        $rows = $query
            ->orderByRaw('coalesce(last_message_at, created_at) desc')
            ->orderByDesc('id')
            ->limit(self::PAGE_SIZE + 1)
            ->get();

        $hasMore = $rows->count() > self::PAGE_SIZE;
        $rows = $rows->take(self::PAGE_SIZE);

        return [
            'items' => $this->mapRows($rows),
            'hasMore' => $hasMore,
            'nextCursor' => $hasMore ? $this->cursorFor($rows->last()) : null,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statuses' => ConversationStatus::options(),
        ];
    }

    /**
     * Meilisearch resolves the ids; ordering and paging stay in SQL.
     *
     * @param  Builder<Conversation>  $query
     */
    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $ids = Conversation::search($search)->take(500)->keys()->all();

        if ($ids === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('id', $ids);
    }

    /**
     * Cursor is `<iso timestamp>|<id>` so paging is stable while new messages
     * keep reordering the list.
     *
     * @param  Builder<Conversation>  $query
     */
    private function applyCursor(Builder $query, string $cursor): void
    {
        if ($cursor === '') {
            return;
        }

        [$timestamp, $id] = array_pad(explode('|', $cursor, 2), 2, null);

        if ($timestamp === null || $id === null || ! ctype_digit($id)) {
            return;
        }

        $query->whereRaw(
            '(coalesce(last_message_at, created_at) < ? or (coalesce(last_message_at, created_at) = ? and id < ?))',
            [$timestamp, $timestamp, (int) $id],
        );
    }

    private function cursorFor(?Conversation $conversation): ?string
    {
        if ($conversation === null) {
            return null;
        }

        $sortedAt = $conversation->last_message_at ?? $conversation->created_at;

        return $sortedAt?->toDateTimeString().'|'.$conversation->id;
    }

    /**
     * @param  Collection<int, Conversation>  $rows
     * @return list<array<string, mixed>>
     */
    private function mapRows(Collection $rows): array
    {
        $unread = ConversationUnreadCounts::forUser($this->viewer);
        $items = [];

        foreach ($rows as $conversation) {
            $items[] = [
                'id' => $conversation->id,
                'order_number' => $conversation->order->order_number,
                'order_status_label' => $conversation->order->status->label(),
                'status' => $conversation->status->value,
                'branch' => $conversation->branch?->only(['id', 'name', 'slug']),
                'last_message_preview' => $conversation->last_message_preview,
                'last_message_at' => $conversation->last_message_at?->toIso8601String(),
                'unread_count' => $unread[$conversation->id] ?? 0,
                'participants' => $conversation->participants
                    ->map(fn (ConversationParticipant $participant): array => [
                        'user_id' => $participant->user_id,
                        'name' => $participant->user->name,
                        'role_label' => $participant->role->label(),
                        'avatar_url' => MediaUrl::resolve($participant->user->avatar, fallback: null),
                    ])
                    ->values()
                    ->all(),
            ];
        }

        return $items;
    }
}
