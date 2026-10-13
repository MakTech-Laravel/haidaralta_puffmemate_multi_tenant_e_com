<?php

use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverAvailabilityStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\NotificationType;
use App\Enums\UserType;
use App\Events\DriverOrdersChanged;
use App\Jobs\FanOutNotificationJob;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\DriverProfile;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\User;
use App\Services\Order\DriverAssignmentService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

/**
 * @return array{fulfillment: OrderFulfillment, driver: User, actor: User, order: Order}
 */
function assignableDriverOrder(): array
{
    $branch = Branch::factory()->create();
    $actor = User::factory()->create(['user_type' => UserType::PLATFORM]);
    $order = Order::factory()->create();
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create([
        'branch_id' => $branch->id,
        'status' => FulfillmentStatus::Ready,
    ]);

    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'is_active' => true,
        'branch_id' => null,
    ]);
    DriverProfile::factory()->create([
        'user_id' => $driver->id,
        'availability_status' => DriverAvailabilityStatus::Available,
    ]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $driver->id,
        'branch_id' => $branch->id,
        'status' => DriverAssignmentStatus::Active,
    ]);
    scheduleDriverOnDuty($driver, $branch);

    return compact('fulfillment', 'driver', 'actor', 'order');
}

test('assigning a driver notifies them and broadcasts an orders refresh', function () {
    Queue::fake([FanOutNotificationJob::class]);
    Event::fake([DriverOrdersChanged::class]);

    ['fulfillment' => $fulfillment, 'driver' => $driver, 'actor' => $actor, 'order' => $order] = assignableDriverOrder();

    app(DriverAssignmentService::class)->assign($fulfillment, $driver, $actor);

    $this->assertDatabaseHas('notifications', [
        'type' => NotificationType::Delivery->value,
        'title' => 'New order assigned',
        'message' => 'Order '.$order->order_number.' has been assigned to you.',
        'created_by' => $actor->id,
    ]);
    $this->assertDatabaseHas('user_notifications', ['user_id' => $driver->id]);
    Queue::assertPushed(FanOutNotificationJob::class);

    Event::assertDispatched(DriverOrdersChanged::class, function (DriverOrdersChanged $event) use ($driver, $order): bool {
        return $event->driverId === $driver->id
            && $event->orderNumber === $order->order_number
            && $event->broadcastAs() === 'driver.orders-changed'
            && $event->broadcastOn()[0]->name === 'private-App.Models.User.'.$driver->id
            && $event->broadcastWith() === ['order_number' => $order->order_number, 'reason' => 'assigned'];
    });
});

test('a rejected assignment sends no notification or refresh', function () {
    Queue::fake([FanOutNotificationJob::class]);
    Event::fake([DriverOrdersChanged::class]);

    ['fulfillment' => $fulfillment, 'actor' => $actor] = assignableDriverOrder();
    $outsider = User::factory()->create(['user_type' => UserType::DRIVER, 'is_active' => true, 'branch_id' => null]);

    expect(fn () => app(DriverAssignmentService::class)->assign($fulfillment, $outsider, $actor))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseCount('user_notifications', 0);
    Queue::assertNotPushed(FanOutNotificationJob::class);
    Event::assertNotDispatched(DriverOrdersChanged::class);
});
