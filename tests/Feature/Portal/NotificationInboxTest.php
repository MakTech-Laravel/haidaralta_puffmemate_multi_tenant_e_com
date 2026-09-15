<?php

use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('guests are redirected to login from platform notifications', function () {
    $this->get(route('platform.notifications.index'))
        ->assertRedirect(route('login'));
});

test('guests are redirected to login from branch notifications', function () {
    $this->get(route('branch.notifications.index', BranchSeeder::SYDNEY_CBD))
        ->assertRedirect(route('login'));
});

test('platform users can view the notifications inbox', function () {
    $this->post(route('login.store'), [
        'phone' => UserSeeder::PLATFORM_SUPER_ADMIN_PHONE,
        'password' => UserSeeder::DEFAULT_PASSWORD,
    ])->assertRedirect();

    $this->get(route('platform.notifications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/notifications/index')
            ->where('portal', 'platform')
        );
});

test('branch users can view the notifications inbox', function () {
    $this->post(route('login.store'), [
        'phone' => UserSeeder::BRANCH_1_SUPER_ADMIN_PHONE,
        'password' => UserSeeder::DEFAULT_PASSWORD,
    ])->assertRedirect();

    $this->get(route('branch.notifications.index', BranchSeeder::SYDNEY_CBD))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branch/notifications/index')
            ->where('portal', 'branch')
            ->where('branch.slug', BranchSeeder::SYDNEY_CBD)
        );
});
