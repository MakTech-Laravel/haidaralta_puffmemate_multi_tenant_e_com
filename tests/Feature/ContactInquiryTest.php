<?php

use App\Enums\ContactInquiryStatus;
use App\Enums\ContactInquiryType;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\ContactInquiry;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

/**
 * @return array<string, mixed>
 */
function contactInquiryPayload(array $overrides = []): array
{
    return [
        'full_name' => 'Alex Contact',
        'phone' => '0412345678',
        'email' => 'alex@example.com',
        'type' => ContactInquiryType::OrderIssue->value,
        'message' => 'Need help with my order.',
        ...$overrides,
    ];
}

test('guest can submit a valid contact inquiry', function () {
    $this->from(route('contact'))
        ->post(route('contact.store'), contactInquiryPayload())
        ->assertRedirect(route('contact'));

    $inquiry = ContactInquiry::query()->first();

    expect($inquiry)->not->toBeNull()
        ->and($inquiry->full_name)->toBe('Alex Contact')
        ->and($inquiry->phone)->toBe('0412345678')
        ->and($inquiry->email)->toBe('alex@example.com')
        ->and($inquiry->type)->toBe(ContactInquiryType::OrderIssue)
        ->and($inquiry->message)->toBe('Need help with my order.')
        ->and($inquiry->status)->toBe(ContactInquiryStatus::New)
        ->and($inquiry->read_at)->toBeNull();
});

test('contact inquiry validation requires name phone type and message', function () {
    $this->from(route('contact'))
        ->post(route('contact.store'), [])
        ->assertRedirect(route('contact'))
        ->assertSessionHasErrors(['full_name', 'phone', 'type', 'message']);

    expect(ContactInquiry::query()->count())->toBe(0);
});

test('contact inquiry rejects invalid type', function () {
    $this->from(route('contact'))
        ->post(route('contact.store'), contactInquiryPayload([
            'type' => 'not-a-real-type',
        ]))
        ->assertRedirect(route('contact'))
        ->assertSessionHasErrors(['type']);

    expect(ContactInquiry::query()->count())->toBe(0);
});

test('platform admin with manage-users can index and show marks inquiry read', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $inquiry = ContactInquiry::factory()->create();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.contact-inquiries.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/contact-inquiries/index')
            ->has('table.data', 1));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.contact-inquiries.show', $inquiry))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/contact-inquiries/show')
            ->where('inquiry.id', $inquiry->id)
            ->where('inquiry.status', ContactInquiryStatus::Read->value)
            ->has('inquiry.type_label'));

    expect($inquiry->fresh()->status)->toBe(ContactInquiryStatus::Read)
        ->and($inquiry->fresh()->read_at)->not->toBeNull();
});

test('guest cannot access contact inquiry platform routes', function () {
    $inquiry = ContactInquiry::factory()->create();

    $this->get(route('platform.contact-inquiries.index'))
        ->assertRedirect();

    $this->get(route('platform.contact-inquiries.show', $inquiry))
        ->assertRedirect();
});

test('platform user without manage-users cannot access contact inquiries', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);

    $inquiry = ContactInquiry::factory()->create();

    $this->actingAs($user, 'platform')
        ->get(route('platform.contact-inquiries.index'))
        ->assertForbidden();

    $this->actingAs($user, 'platform')
        ->get(route('platform.contact-inquiries.show', $inquiry))
        ->assertForbidden();
});
