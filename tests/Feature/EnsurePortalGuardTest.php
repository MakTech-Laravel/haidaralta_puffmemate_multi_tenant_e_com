<?php

use App\Enums\UserType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('portal guard redirects to login when users table is missing mid-request', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    $this->actingAs($user, 'platform');

    Schema::disableForeignKeyConstraints();
    Schema::drop('users');
    Schema::enableForeignKeyConstraints();

    Auth::guard('platform')->forgetUser();

    $response = $this->get(route('platform.dashboard'));

    expect(Schema::hasTable('users'))->toBeFalse();

    $response->assertRedirect(route('login'));
});
