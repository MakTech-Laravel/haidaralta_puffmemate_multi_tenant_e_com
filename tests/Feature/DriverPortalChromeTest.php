<?php

use App\Enums\UserType;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot open driver field tabs', function (string $route) {
    $this->get(route($route))->assertRedirect();
})->with([
    'chats' => 'driver.chats.index',
    'notifications' => 'driver.notifications.index',
    'account' => 'driver.account.index',
]);

test('drivers can open the field tab destinations', function (string $route, string $component) {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route($route))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    'chats' => ['driver.chats.index', 'driver/chats/index'],
    'notifications' => ['driver.notifications.index', 'driver/notifications/index'],
    'account' => ['driver.account.index', 'driver/account/index'],
]);
