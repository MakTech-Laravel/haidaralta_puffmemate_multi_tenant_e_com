<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from customer notifications', function () {
    $this->get(route('customer.notifications.index'))
        ->assertRedirect(route('login'));
});

test('customers can view the demo notifications page', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'customer');

    $this->get(route('customer.notifications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/notifications/index')
            ->has('notifications')
            ->where('notifications.0.id', 'notif-1')
            ->where('notifications.0.title', 'Order shipped')
            ->where('notifications.0.read', false)
            ->where('notifications.0.type', 'order')
            ->has('notifications', 6));
});

test('guests are redirected from customer notification detail', function () {
    $this->get(route('customer.notifications.show', 'notif-1'))
        ->assertRedirect(route('login'));
});

test('customers can view a demo notification detail page', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'customer');

    $this->get(route('customer.notifications.show', 'notif-1'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/notifications/show')
            ->where('notification.id', 'notif-1')
            ->where('notification.title', 'Order shipped')
            ->has('notification.body'));
});

test('customers receive not found for unknown demo notifications', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'customer');

    $this->get(route('customer.notifications.show', 'notif-missing'))
        ->assertNotFound();
});
