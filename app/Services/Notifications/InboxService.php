<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InboxService
{
    public function unreadCount(User $user): int
    {
        return UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function preview(User $user, int $limit = 5): array
    {
        return UserNotification::query()
            ->where('user_id', $user->id)
            ->with('notification')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (UserNotification $row): array => $this->toItem($row))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listFor(User $user): array
    {
        return UserNotification::query()
            ->where('user_id', $user->id)
            ->with('notification')
            ->latest('id')
            ->get()
            ->map(fn (UserNotification $row): array => $this->toItem($row))
            ->values()
            ->all();
    }

    public function findFor(User $user, int $id): UserNotification
    {
        return UserNotification::query()
            ->where('user_id', $user->id)
            ->with('notification')
            ->findOrFail($id);
    }

    public function markRead(User $user, UserNotification $row): void
    {
        $this->ensureOwner($user, $row);

        if ($row->read_at === null) {
            $row->update(['read_at' => now()]);
        }
    }

    public function delete(User $user, UserNotification $row): void
    {
        $this->ensureOwner($user, $row);

        $row->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function toItem(UserNotification $row): array
    {
        $notification = $row->notification;
        $data = is_array($notification?->data) ? $notification->data : [];

        return [
            'id' => (string) $row->id,
            'title' => $notification?->title ?? '',
            'description' => $notification?->message ?? '',
            'createdAt' => $row->created_at?->diffForHumans() ?? '',
            'read' => $row->read_at !== null,
            'type' => $notification?->type->value,
            'body' => $notification?->message ?? '',
            'icon' => is_string($data['icon'] ?? null) ? $data['icon'] : null,
            'href' => is_string($data['link_url'] ?? null) ? $data['link_url'] : null,
            'linkLabel' => is_string($data['link_label'] ?? null) ? $data['link_label'] : null,
        ];
    }

    private function ensureOwner(User $user, UserNotification $row): void
    {
        if ($row->user_id !== $user->id) {
            throw (new ModelNotFoundException)->setModel(UserNotification::class, [$row->id]);
        }
    }
}
