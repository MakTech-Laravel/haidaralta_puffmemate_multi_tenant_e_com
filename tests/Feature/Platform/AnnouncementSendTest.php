<?php

use App\Enums\AnnouncementType;
use App\Enums\NotificationType;
use App\Enums\UserType;
use App\Jobs\FanOutNotificationJob;
use App\Jobs\SendAnnouncementEmailJob;
use App\Jobs\SendAnnouncementPhoneJob;
use App\Jobs\SendFcmPushJob;
use App\Mail\AnnouncementMail;
use App\Models\Announcement;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Sms\FakeSmsSender;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

function announcementAdmin(): User
{
    return User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
}

test('group send creates an announcement and inbox rows for the audience', function () {
    Queue::fake([FanOutNotificationJob::class]);

    $admin = announcementAdmin();
    $expected = User::query()
        ->where('is_active', true)
        ->where('user_type', UserType::PLATFORM)
        ->count();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.announcements.store'), [
            'send_mode' => 'group',
            'audiences' => ['platform'],
            'type' => AnnouncementType::Info->value,
            'icon' => 'bell',
            'title' => 'Platform notice',
            'message' => 'Please read this.',
        ])
        ->assertRedirect(route('platform.announcements.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Notification sent.',
        ]);

    $announcement = Announcement::query()->latest('id')->first();

    expect($announcement)->not->toBeNull()
        ->and($announcement->recipient_count)->toBe($expected)
        ->and($announcement->via_system)->toBeTrue()
        ->and($announcement->notification_id)->not->toBeNull();

    $this->assertDatabaseCount('notifications', 1);
    $this->assertDatabaseCount('user_notifications', $expected);
    $this->assertDatabaseHas('notifications', [
        'type' => NotificationType::Info->value,
        'title' => 'Platform notice',
    ]);

    Queue::assertPushed(FanOutNotificationJob::class);
});

test('send with a link stores the url and label on the notification', function () {
    Queue::fake([FanOutNotificationJob::class]);

    $admin = announcementAdmin();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.announcements.store'), [
            'send_mode' => 'group',
            'audiences' => ['platform'],
            'type' => AnnouncementType::Info->value,
            'icon' => 'bell',
            'title' => 'Linked notice',
            'message' => 'Open the shop.',
            'link_label' => 'Shop now',
            'link_url' => 'https://example.com/shop',
        ])
        ->assertRedirect(route('platform.announcements.index'));

    $notification = Notification::query()->latest('id')->first();

    expect($notification)->not->toBeNull()
        ->and($notification->data['link_label'])->toBe('Shop now')
        ->and($notification->data['link_url'])->toBe('https://example.com/shop');
});

test('individual send notifies only the selected people', function () {
    Queue::fake([FanOutNotificationJob::class]);

    $admin = announcementAdmin();
    $customer = User::factory()->create(['user_type' => UserType::CUSTOMER]);
    $other = User::factory()->create(['user_type' => UserType::CUSTOMER]);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.announcements.store'), [
            'send_mode' => 'individual',
            'recipients' => [
                ['id' => $customer->id, 'audience' => 'customer'],
            ],
            'type' => 'promo',
            'icon' => 'tag',
            'title' => 'Promo',
            'message' => 'Save today.',
        ])
        ->assertRedirect(route('platform.announcements.index'));

    expect(UserNotification::query()->where('user_id', $customer->id)->count())->toBe(1)
        ->and(UserNotification::query()->where('user_id', $other->id)->count())->toBe(0);
});

test('individual send rejects a recipient from the wrong audience', function () {
    $admin = announcementAdmin();
    $customer = User::factory()->create(['user_type' => UserType::CUSTOMER]);

    $this->actingAs($admin, 'platform')
        ->from(route('platform.announcements.index'))
        ->post(route('platform.announcements.store'), [
            'send_mode' => 'individual',
            'recipients' => [
                ['id' => $customer->id, 'audience' => 'driver'],
            ],
            'type' => 'info',
            'icon' => 'bell',
            'title' => 'Hello',
            'message' => 'World',
        ])
        ->assertRedirect(route('platform.announcements.index'))
        ->assertSessionHasErrors('recipients');

    $this->assertDatabaseCount('announcements', 0);
    $this->assertDatabaseCount('notifications', 0);
});

test('empty group or individual lists are rejected', function () {
    $admin = announcementAdmin();

    $this->actingAs($admin, 'platform')
        ->from(route('platform.announcements.index'))
        ->post(route('platform.announcements.store'), [
            'send_mode' => 'group',
            'audiences' => [],
            'type' => 'info',
            'icon' => 'bell',
            'title' => 'Hello',
            'message' => 'World',
        ])
        ->assertSessionHasErrors('audiences');

    $this->actingAs($admin, 'platform')
        ->from(route('platform.announcements.index'))
        ->post(route('platform.announcements.store'), [
            'send_mode' => 'individual',
            'recipients' => [],
            'type' => 'info',
            'icon' => 'bell',
            'title' => 'Hello',
            'message' => 'World',
        ])
        ->assertSessionHasErrors('recipients');
});

test('phone and email flags queue channel jobs without sending push', function () {
    Queue::fake([
        FanOutNotificationJob::class,
        SendFcmPushJob::class,
        SendAnnouncementPhoneJob::class,
        SendAnnouncementEmailJob::class,
    ]);
    Mail::fake();

    $admin = announcementAdmin();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.announcements.store'), [
            'send_mode' => 'group',
            'audiences' => ['platform'],
            'type' => 'alert',
            'icon' => 'megaphone',
            'title' => 'Alert',
            'message' => 'Heads up.',
            'via_phone' => true,
            'via_email' => true,
            'via_push' => false,
        ])
        ->assertRedirect(route('platform.announcements.index'));

    $announcement = Announcement::query()->latest('id')->first();

    expect($announcement->via_phone)->toBeTrue()
        ->and($announcement->via_email)->toBeTrue()
        ->and($announcement->via_push)->toBeFalse()
        ->and($announcement->via_system)->toBeTrue();

    $emailUserIds = User::query()
        ->where('is_active', true)
        ->where('user_type', UserType::PLATFORM)
        ->whereNotNull('email')
        ->where('email', '!=', '')
        ->orderBy('id')
        ->pluck('id')
        ->map(fn (int|string $id): int => (int) $id)
        ->values();

    Queue::assertPushed(FanOutNotificationJob::class);
    Queue::assertPushed(SendAnnouncementPhoneJob::class);
    Queue::assertPushedTimes(SendAnnouncementEmailJob::class, $emailUserIds->count());
    Queue::assertNotPushed(SendFcmPushJob::class);

    $emailUserIds->each(function (int $userId): void {
        Queue::assertPushed(SendAnnouncementEmailJob::class, function (SendAnnouncementEmailJob $job) use ($userId): bool {
            return $job->userId === $userId
                && $job->title === 'Alert'
                && $job->message === 'Heads up.'
                && $job->delay === null;
        });
    });
    Mail::assertNothingOutgoing();
    $this->assertDatabaseCount('notifications', 1);
});

test('email goes only to recipients with an address and phone logs for each recipient', function () {
    Queue::fake([FanOutNotificationJob::class, SendFcmPushJob::class]);
    Mail::fake();

    $admin = announcementAdmin();
    $withEmail = User::factory()->create([
        'user_type' => UserType::CUSTOMER,
        'name' => 'Has Mail',
        'email' => 'with-mail@example.com',
    ]);
    $withoutEmail = User::factory()->create([
        'user_type' => UserType::CUSTOMER,
        'name' => 'No Mail',
        'email' => null,
    ]);

    $sms = app(FakeSmsSender::class);
    $sms->reset();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.announcements.store'), [
            'send_mode' => 'individual',
            'recipients' => [
                ['id' => $withEmail->id, 'audience' => 'customer'],
                ['id' => $withoutEmail->id, 'audience' => 'customer'],
            ],
            'type' => 'info',
            'icon' => 'bell',
            'title' => 'Channel notice',
            'message' => 'Please read.',
            'link_label' => 'Shop now',
            'link_url' => 'https://example.com/shop',
            'via_phone' => true,
            'via_email' => true,
        ])
        ->assertRedirect(route('platform.announcements.index'));

    Mail::assertSent(AnnouncementMail::class, 1);
    Mail::assertSent(AnnouncementMail::class, function (AnnouncementMail $mail) use ($withEmail): bool {
        return $mail->hasTo($withEmail->email)
            && $mail->title === 'Channel notice'
            && $mail->body === 'Please read.'
            && $mail->linkUrl === 'https://example.com/shop';
    });

    expect($sms->messages)->toHaveCount(2);

    $phones = collect($sms->messages)->pluck('to')->all();

    expect($phones)->toContain($withEmail->phone)
        ->and($phones)->toContain($withoutEmail->phone)
        ->and($sms->messages[0]['message'])->toContain('Channel notice')
        ->and($sms->messages[0]['message'])->toContain('Please read.')
        ->and($sms->messages[0]['message'])->toContain('https://example.com/shop');
});

test('announcement email job is paced by the announcement-mail limiter', function () {
    $job = new SendAnnouncementEmailJob(
        userId: 1,
        title: 'Pace',
        message: 'Check.',
    );

    expect($job->middleware())->toHaveCount(1)
        ->and($job->middleware()[0])->toBeInstanceOf(RateLimited::class);
});

test('announcement mail template includes the title and link', function () {
    $mailable = new AnnouncementMail(
        title: 'Sale',
        body: 'Save today.',
        recipientName: 'Alex',
        linkLabel: 'Shop now',
        linkUrl: 'https://example.com/shop',
    );

    $mailable->assertSeeInHtml('Sale')
        ->assertSeeInHtml('Save today.')
        ->assertSeeInHtml('Alex')
        ->assertSeeInHtml('https://example.com/shop')
        ->assertSeeInHtml('Shop now')
        ->assertSeeInHtml('#C4472F')
        ->assertSeeInText('Sale')
        ->assertSeeInText('https://example.com/shop');
});

test('push queues an fcm job for the announcement recipients', function () {
    Queue::fake([FanOutNotificationJob::class, SendFcmPushJob::class]);

    $admin = announcementAdmin();
    $userIds = User::query()
        ->where('is_active', true)
        ->where('user_type', UserType::PLATFORM)
        ->orderBy('id')
        ->pluck('id')
        ->map(fn (int|string $id): int => (int) $id)
        ->all();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.announcements.store'), [
            'send_mode' => 'group',
            'audiences' => ['platform'],
            'type' => 'info',
            'icon' => 'bell',
            'title' => 'Push notice',
            'message' => 'Check the shop.',
            'via_push' => true,
            'link_label' => 'Shop now',
            'link_url' => 'https://example.com/shop',
        ])
        ->assertRedirect(route('platform.announcements.index'));

    $announcement = Announcement::query()->latest('id')->first();

    expect($announcement)->not->toBeNull()
        ->and($announcement->via_push)->toBeTrue();

    Queue::assertPushed(FanOutNotificationJob::class);
    Queue::assertPushed(SendFcmPushJob::class, function (SendFcmPushJob $job) use ($userIds, $announcement): bool {
        return $job->userIds === $userIds
            && $job->title === 'Push notice'
            && $job->body === 'Check the shop.'
            && $job->data['announcement_id'] === (string) $announcement->id
            && $job->data['url'] === 'https://example.com/shop'
            && $job->data['link_label'] === 'Shop now';
    });
});

test('push without a compose link opens home', function () {
    Queue::fake([FanOutNotificationJob::class, SendFcmPushJob::class]);

    $admin = announcementAdmin();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.announcements.store'), [
            'send_mode' => 'group',
            'audiences' => ['platform'],
            'type' => 'info',
            'icon' => 'bell',
            'title' => 'Home notice',
            'message' => 'No link.',
            'via_push' => true,
        ])
        ->assertRedirect(route('platform.announcements.index'));

    Queue::assertPushed(SendFcmPushJob::class, function (SendFcmPushJob $job): bool {
        return $job->title === 'Home notice'
            && $job->data['url'] === url('/');
    });
});

test('guests cannot send announcements', function () {
    $this->post(route('platform.announcements.store'), [
        'send_mode' => 'group',
        'audiences' => ['platform'],
        'type' => 'info',
        'icon' => 'bell',
        'title' => 'Hello',
        'message' => 'World',
    ])->assertRedirect(route('login'));
});

test('platform admin can search announcement recipients', function () {
    $admin = announcementAdmin();
    $customer = User::factory()->create([
        'user_type' => UserType::CUSTOMER,
        'name' => 'Searchable Recipient',
    ]);

    $this->actingAs($admin, 'platform')
        ->getJson(route('platform.announcements.recipients', [
            'audience' => 'customer',
            'q' => 'Searchable',
        ]))
        ->assertOk()
        ->assertJsonFragment([
            'id' => $customer->id,
            'name' => 'Searchable Recipient',
        ]);
});
