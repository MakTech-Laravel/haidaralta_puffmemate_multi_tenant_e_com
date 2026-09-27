<?php

use App\Enums\NotificationType;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from customer notifications', function () {
    $this->get(route('customer.notifications.index'))
        ->assertRedirect(route('login'));
});

test('customers can view their notifications inbox', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'customer');

    notify_users(
        userIds: [$user->id],
        type: NotificationType::Order,
        title: 'Order shipped',
        message: 'Your order is on the way.',
    );

    $this->get(route('customer.notifications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/notifications/index')
            ->has('items', 1)
            ->where('items.0.title', 'Order shipped')
            ->where('items.0.read', false)
            ->where('items.0.type', 'order'));
});

test('guests are redirected from customer notification detail', function () {
    $user = User::factory()->create();

    notify_users(
        userIds: [$user->id],
        type: NotificationType::Order,
        title: 'Order shipped',
        message: 'Your order is on the way.',
    );

    $row = $user->userNotifications()->firstOrFail();

    $this->get(route('customer.notifications.show', $row->id))
        ->assertRedirect(route('login'));
});

test('customers can view a notification detail page', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'customer');

    notify_users(
        userIds: [$user->id],
        type: NotificationType::Order,
        title: 'Order shipped',
        message: 'Your order is on the way.',
    );

    $row = $user->userNotifications()->firstOrFail();

    $this->get(route('customer.notifications.show', $row->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/notifications/show')
            ->where('notification.id', (string) $row->id)
            ->where('notification.title', 'Order shipped')
            ->has('notification.body'));
});

test('customers receive not found for unknown notifications', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'customer');

    $this->get(route('customer.notifications.show', 999_999))
        ->assertNotFound();
});
