<?php

use App\Enums\NotificationType;
use App\Events\UserNotificationCreated;
use App\Jobs\FanOutNotificationJob;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

test('creates one parent and a row per unique recipient then queues fan-out', function () {
    $sender = User::factory()->create();
    $buyer = User::factory()->create();
    $driver = User::factory()->create();

    Queue::fake([FanOutNotificationJob::class]);

    $notification = notify_users(
        userIds: [$buyer->id, $buyer->id, $driver->id],
        type: NotificationType::Order,
        title: 'Order assigned',
        message: 'Driver is on the way.',
        data: ['order_id' => 42],
        createdBy: $sender,
    );

    expect($notification)->not->toBeNull();

    $this->assertDatabaseCount('notifications', 1);
    $this->assertDatabaseHas('notifications', [
        'id' => $notification->id,
        'type' => NotificationType::Order->value,
        'title' => 'Order assigned',
        'message' => 'Driver is on the way.',
        'created_by' => $sender->id,
    ]);
    $this->assertDatabaseCount('user_notifications', 2);
    $this->assertDatabaseHas('user_notifications', [
        'notification_id' => $notification->id,
        'user_id' => $buyer->id,
    ]);
    $this->assertDatabaseHas('user_notifications', [
        'notification_id' => $notification->id,
        'user_id' => $driver->id,
    ]);

    Queue::assertPushed(
        FanOutNotificationJob::class,
        fn (FanOutNotificationJob $job): bool => $job->notificationId === $notification->id,
    );
});

test('does not persist or queue when the recipient list is empty', function () {
    Queue::fake();

    $notification = notify_users(
        userIds: [],
        type: NotificationType::Order,
        title: 'Order assigned',
        message: 'Driver is on the way.',
    );

    expect($notification)->toBeNull();
    $this->assertDatabaseCount('notifications', 0);
    $this->assertDatabaseCount('user_notifications', 0);
    Queue::assertNothingPushed();
});

test('broadcasts a private notification.created event for each recipient', function () {
    $buyer = User::factory()->create();
    $driver = User::factory()->create();

    Event::fake([UserNotificationCreated::class]);

    notify_users(
        userIds: [$buyer, $driver],
        type: NotificationType::Delivery,
        title: 'Out for delivery',
        message: 'Your order is on the way.',
        data: ['order_id' => 7],
    );

    Event::assertDispatchedTimes(UserNotificationCreated::class, 2);
    Event::assertDispatched(function (UserNotificationCreated $event) use ($buyer): bool {
        $channel = $event->broadcastOn()[0];
        $payload = $event->broadcastWith();

        return $event->userNotification->user_id === $buyer->id
            && $event->broadcastAs() === UserNotificationCreated::BROADCAST_AS
            && $channel instanceof PrivateChannel
            && $channel->name === 'private-App.Models.User.'.$buyer->id
            && $payload['type'] === NotificationType::Delivery->value
            && $payload['title'] === 'Out for delivery'
            && $payload['message'] === 'Your order is on the way.'
            && $payload['data'] === ['order_id' => 7];
    });
});

test('soft-deleted recipient row keeps the unique pair', function () {
    $recipient = UserNotification::factory()->create();

    $recipient->delete();

    expect(fn () => UserNotification::query()->create([
        'notification_id' => $recipient->notification_id,
        'user_id' => $recipient->user_id,
    ]))->toThrow(UniqueConstraintViolationException::class);
});
