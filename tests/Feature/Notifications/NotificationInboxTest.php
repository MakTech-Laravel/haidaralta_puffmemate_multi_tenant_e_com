<?php

use App\Enums\NotificationType;
use App\Enums\UserType;
use App\Models\User;
use App\Models\UserNotification;
use Inertia\Testing\AssertableInertia as Assert;

test('a recipient sees their inbox and shared preview', function () {
    $user = User::factory()->create(['user_type' => UserType::CUSTOMER]);

    notify_users(
        userIds: [$user->id],
        type: NotificationType::Info,
        title: 'Inbox notice',
        message: 'You have a new message.',
        data: ['icon' => 'bell'],
    );

    $this->actingAs($user, 'customer')
        ->get(route('customer.notifications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/notifications/index')
            ->has('items', 1)
            ->where('items.0.title', 'Inbox notice')
            ->where('notifications.unreadCount', 1)
            ->has('notifications.preview', 1));
});

test('inbox items include the announcement link and label', function () {
    $user = User::factory()->create(['user_type' => UserType::CUSTOMER]);

    notify_users(
        userIds: [$user->id],
        type: NotificationType::Info,
        title: 'Sale',
        message: 'Save today.',
        data: [
            'icon' => 'tag',
            'link_label' => 'Shop now',
            'link_url' => 'https://example.com/shop',
        ],
    );

    $row = UserNotification::query()->where('user_id', $user->id)->firstOrFail();

    $this->actingAs($user, 'customer')
        ->get(route('customer.notifications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/notifications/index')
            ->where('items.0.href', 'https://example.com/shop')
            ->where('items.0.linkLabel', 'Shop now'));

    $this->actingAs($user, 'customer')
        ->get(route('customer.notifications.show', $row->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/notifications/show')
            ->where('notification.href', 'https://example.com/shop')
            ->where('notification.linkLabel', 'Shop now'));
});

test('a user cannot open another users notification', function () {
    $owner = User::factory()->create(['user_type' => UserType::CUSTOMER]);
    $other = User::factory()->create(['user_type' => UserType::CUSTOMER]);

    notify_users(
        userIds: [$owner->id],
        type: NotificationType::Info,
        title: 'Private',
        message: 'Only for the owner.',
    );

    $row = UserNotification::query()->where('user_id', $owner->id)->firstOrFail();

    $this->actingAs($other, 'customer')
        ->get(route('customer.notifications.show', $row->id))
        ->assertNotFound();
});

test('the owner can open and soft delete their notification', function () {
    $user = User::factory()->create(['user_type' => UserType::CUSTOMER]);

    notify_users(
        userIds: [$user->id],
        type: NotificationType::Alert,
        title: 'Keep this',
        message: 'Until deleted.',
    );

    $row = UserNotification::query()->where('user_id', $user->id)->firstOrFail();

    $this->actingAs($user, 'customer')
        ->get(route('customer.notifications.show', $row->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/notifications/show')
            ->where('notification.title', 'Keep this')
            ->where('notification.read', true));

    $this->actingAs($user, 'customer')
        ->delete(route('customer.notifications.destroy', $row->id))
        ->assertRedirect(route('customer.notifications.index'));

    expect(UserNotification::query()->find($row->id))->toBeNull()
        ->and(UserNotification::withTrashed()->find($row->id))->not->toBeNull();
});
