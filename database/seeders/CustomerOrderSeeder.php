<?php

namespace Database\Seeders;

use App\Actions\Order\PlaceOrderAction;
use App\Enums\CartStatus;
use App\Enums\DeliveryFailureReason;
use App\Enums\FulfillmentStatus;
use App\Enums\NotificationType;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderDriverAssignmentHistory;
use App\Models\OrderFulfillment;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Product;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use App\Services\Order\DriverDeliveryService;
use App\Services\Order\FulfillmentStatusService;
use App\Services\Order\OrderPlacementNotifier;
use App\Support\BranchProductPriceDisplay;
use Carbon\CarbonInterface;
use Database\Seeders\Concerns\ReportsSeedProgress;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Real checkout orders for the demo customers, walked through the same services
 * the portals use, so every status from pending to returned has stock
 * reservations, history, payments, and delivery codes that line up.
 *
 * Every driver step goes to Daniel Foster (UserSeeder::DRIVER_PHONE) except one
 * pair per customer, which is assigned to Marcus Reid (UserSeeder::SECOND_DRIVER_PHONE).
 */
class CustomerOrderSeeder extends Seeder
{
    use ReportsSeedProgress;

    /**
     * @var array<string, array{address: string, city: string, postal_code: string, latitude: float, longitude: float}>
     */
    private const DELIVERY_ADDRESSES = [
        UserSeeder::CUSTOMER_PHONE => ['address' => 'Level 4, 88 Liverpool Street', 'city' => 'Sydney', 'postal_code' => '2000', 'latitude' => -33.8768, 'longitude' => 151.2063],
        '+61499000001' => ['address' => 'Unit 12, 50 Murray Street', 'city' => 'Pyrmont', 'postal_code' => '2009', 'latitude' => -33.8721, 'longitude' => 151.1985],
        '+61499000002' => ['address' => '21 Harris Street', 'city' => 'Ultimo', 'postal_code' => '2007', 'latitude' => -33.8790, 'longitude' => 151.1990],
        '+61499000003' => ['address' => 'Apartment 1806, 310 Pitt Street', 'city' => 'Sydney', 'postal_code' => '2000', 'latitude' => -33.8752, 'longitude' => 151.2078],
    ];

    /**
     * Sydney CBD shelf products without variants (product slug => quantity).
     *
     * @var list<array<string, int>>
     */
    private const BASKETS = [
        ['winfield-blue-soft-pack-20s' => 2, 'clipper-classic-lighter' => 1],
        ['puffmemate-mini-bar-1200' => 2, 'usb-c-charge-cable-1m' => 1],
        ['nordic-mint-pouches' => 1, 'raw-classic-rolling-papers-ks' => 2],
        ['horizon-red-soft-pack-25s' => 1, 'bic-maxi-lighter' => 2],
        ['terea-tobacco-sticks-20' => 2],
        ['velo-cool-mint-pouches' => 1, 'puffmemate-cloud-bar-6000' => 1],
        ['rothmans-blue-soft-pack-20s' => 1, 'clipper-classic-lighter' => 1],
        ['winfield-red-soft-pack-20s' => 1, 'peter-jackson-menthol-20s' => 1],
    ];

    /** @var array<string, Product> */
    private array $products = [];

    public function __construct(
        private PlaceOrderAction $placeOrder,
        private FulfillmentStatusService $statuses,
        private DriverDeliveryService $delivery,
        private OrderPlacementNotifier $placements,
        private NotificationService $notifications,
    ) {}

    /**
     * Placement opens as "pending" until the customer has a delivered order,
     * so the pending and hold scenarios come first.
     *
     * @return list<array{status: FulfillmentStatus, driver?: string, cancelled_by?: string, after?: FulfillmentStatus, minutes_ago: int, notes?: string}>
     */
    private function scenarios(): array
    {
        return [
            ['status' => FulfillmentStatus::Pending, 'minutes_ago' => 25, 'notes' => 'Please call when you are downstairs.'],
            ['status' => FulfillmentStatus::Pending, 'minutes_ago' => 40],
            ['status' => FulfillmentStatus::Hold, 'minutes_ago' => 360, 'notes' => 'Buzzer is broken, knock on the glass door.'],
            ['status' => FulfillmentStatus::Delivered, 'driver' => UserSeeder::DRIVER_PHONE, 'minutes_ago' => 5_760],
            ['status' => FulfillmentStatus::Delivered, 'driver' => UserSeeder::DRIVER_PHONE, 'minutes_ago' => 4_320],
            ['status' => FulfillmentStatus::Placed, 'minutes_ago' => 50],
            ['status' => FulfillmentStatus::Placed, 'minutes_ago' => 75],
            ['status' => FulfillmentStatus::Processing, 'minutes_ago' => 95],
            ['status' => FulfillmentStatus::Processing, 'minutes_ago' => 110],
            ['status' => FulfillmentStatus::Ready, 'minutes_ago' => 130],
            ['status' => FulfillmentStatus::Ready, 'minutes_ago' => 150],
            ['status' => FulfillmentStatus::Assigned, 'driver' => UserSeeder::DRIVER_PHONE, 'minutes_ago' => 160],
            ['status' => FulfillmentStatus::Assigned, 'driver' => UserSeeder::DRIVER_PHONE, 'minutes_ago' => 170],
            ['status' => FulfillmentStatus::Assigned, 'driver' => UserSeeder::SECOND_DRIVER_PHONE, 'minutes_ago' => 175],
            ['status' => FulfillmentStatus::Assigned, 'driver' => UserSeeder::SECOND_DRIVER_PHONE, 'minutes_ago' => 185],
            ['status' => FulfillmentStatus::PickedUp, 'driver' => UserSeeder::DRIVER_PHONE, 'minutes_ago' => 190],
            ['status' => FulfillmentStatus::PickedUp, 'driver' => UserSeeder::DRIVER_PHONE, 'minutes_ago' => 200],
            ['status' => FulfillmentStatus::OutForDelivery, 'driver' => UserSeeder::DRIVER_PHONE, 'minutes_ago' => 210],
            ['status' => FulfillmentStatus::OutForDelivery, 'driver' => UserSeeder::DRIVER_PHONE, 'minutes_ago' => 220],
            ['status' => FulfillmentStatus::Arrived, 'driver' => UserSeeder::DRIVER_PHONE, 'minutes_ago' => 230],
            ['status' => FulfillmentStatus::Arrived, 'driver' => UserSeeder::DRIVER_PHONE, 'minutes_ago' => 240],
            ['status' => FulfillmentStatus::DeliveryFailed, 'driver' => UserSeeder::DRIVER_PHONE, 'minutes_ago' => 300],
            ['status' => FulfillmentStatus::DeliveryFailed, 'driver' => UserSeeder::DRIVER_PHONE, 'minutes_ago' => 320],
            ['status' => FulfillmentStatus::Returning, 'driver' => UserSeeder::DRIVER_PHONE, 'minutes_ago' => 420],
            ['status' => FulfillmentStatus::Returning, 'driver' => UserSeeder::DRIVER_PHONE, 'minutes_ago' => 450],
            ['status' => FulfillmentStatus::Returned, 'driver' => UserSeeder::DRIVER_PHONE, 'minutes_ago' => 1_440],
            ['status' => FulfillmentStatus::Returned, 'driver' => UserSeeder::DRIVER_PHONE, 'minutes_ago' => 1_560],
            ['status' => FulfillmentStatus::Cancelled, 'cancelled_by' => 'customer', 'after' => FulfillmentStatus::Placed, 'minutes_ago' => 2_880],
            ['status' => FulfillmentStatus::Cancelled, 'cancelled_by' => 'staff', 'after' => FulfillmentStatus::Ready, 'minutes_ago' => 3_000],
        ];
    }

    public function run(): void
    {
        $previousBroadcaster = config('broadcasting.default');
        config(['broadcasting.default' => 'null']);

        try {
            $this->seedOrders();
        } finally {
            config(['broadcasting.default' => $previousBroadcaster]);
        }
    }

    private function seedOrders(): void
    {
        $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
        $staff = User::query()->where('phone', UserSeeder::BRANCH_1_ADMIN_PHONE)->firstOrFail();
        $drivers = User::query()
            ->whereIn('phone', [UserSeeder::DRIVER_PHONE, UserSeeder::SECOND_DRIVER_PHONE])
            ->get()
            ->keyBy('phone');

        $this->products = Product::query()
            ->whereIn('slug', collect(self::BASKETS)->flatMap(fn (array $basket): array => array_keys($basket))->unique()->all())
            ->get()
            ->keyBy('slug')
            ->all();

        $customers = User::query()
            ->whereIn('phone', UserSeeder::CUSTOMERS_WITH_ORDERS_PHONES)
            ->whereNotIn('id', Order::query()->whereNotNull('customer_id')->select('customer_id'))
            ->orderBy('id')
            ->get()
            ->values();

        $jobs = [];

        foreach ($customers as $customerIndex => $customer) {
            foreach ($this->scenarios() as $scenarioIndex => $scenario) {
                $jobs[] = [$customer, $scenario, $customerIndex + $scenarioIndex, $scenarioIndex === 0];
            }
        }

        $this->withSeedProgress('Orders', $jobs, function (array $job) use ($sydney, $staff, $drivers): void {
            [$customer, $scenario, $basketIndex, $saveAddress] = $job;

            $order = $this->place($customer, self::BASKETS[$basketIndex % count(self::BASKETS)], $scenario, $saveAddress);
            $fulfillment = $order->fulfillment;

            if ($fulfillment === null || $fulfillment->branch_id !== $sydney->id || $fulfillment->stockTransfers()->exists()) {
                throw new RuntimeException('Order '.$order->order_number.' was not fulfilled from Sydney CBD stock.');
            }

            $driver = isset($scenario['driver']) ? $drivers->get($scenario['driver']) : null;

            foreach ($this->path($scenario) as $step) {
                $fulfillment = $this->step($fulfillment->refresh(), $step, $scenario, $customer, $staff, $driver);
            }

            $this->backdate($order, now()->subMinutes($scenario['minutes_ago']));
        }, fn (int $total): string => sprintf('%d customer orders across every status ready.', $total));
    }

    /**
     * @param  array<string, int>  $basket
     * @param  array{notes?: string}  $scenario
     */
    private function place(User $customer, array $basket, array $scenario, bool $saveAddress): Order
    {
        $cart = Cart::query()->create([
            'user_id' => $customer->id,
            'status' => CartStatus::Active,
        ]);

        foreach ($basket as $slug => $quantity) {
            $product = $this->products[$slug];

            CartItem::query()->create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'variant_id' => null,
                'quantity' => $quantity,
                'price_snapshot' => BranchProductPriceDisplay::catalogEffectiveAmount($product),
            ]);
        }

        return $this->placeOrder->handle($customer, $cart, [
            'name' => $customer->name,
            'mobile' => $customer->phone,
            ...self::DELIVERY_ADDRESSES[$customer->phone],
            'save_as_default' => $saveAddress,
            'delivery_method' => 'regular',
            'delivery_date' => null,
            'time_slot' => null,
            'payment_method' => PaymentGatewaySetting::KEY_COD,
            'notes' => $scenario['notes'] ?? null,
        ]);
    }

    /**
     * @param  array{status: FulfillmentStatus, after?: FulfillmentStatus}  $scenario
     * @return list<FulfillmentStatus>
     */
    private function path(array $scenario): array
    {
        $delivery = [
            FulfillmentStatus::Placed,
            FulfillmentStatus::Processing,
            FulfillmentStatus::Ready,
            FulfillmentStatus::Assigned,
            FulfillmentStatus::PickedUp,
            FulfillmentStatus::OutForDelivery,
            FulfillmentStatus::Arrived,
        ];
        $return = [
            ...$delivery,
            FulfillmentStatus::DeliveryFailed,
            FulfillmentStatus::Returning,
            FulfillmentStatus::Returned,
        ];
        $upTo = fn (array $ladder, FulfillmentStatus $status): array => array_slice($ladder, 0, array_search($status, $ladder, true) + 1);

        return match ($scenario['status']) {
            FulfillmentStatus::Pending => [],
            FulfillmentStatus::Hold => [FulfillmentStatus::Hold],
            FulfillmentStatus::Delivered => [...$delivery, FulfillmentStatus::Delivered],
            FulfillmentStatus::DeliveryFailed, FulfillmentStatus::Returning, FulfillmentStatus::Returned => $upTo($return, $scenario['status']),
            FulfillmentStatus::Cancelled => [...$upTo($delivery, $scenario['after'] ?? FulfillmentStatus::Placed), FulfillmentStatus::Cancelled],
            default => $upTo($delivery, $scenario['status']),
        };
    }

    /**
     * @param  array{cancelled_by?: string}  $scenario
     */
    private function step(
        OrderFulfillment $fulfillment,
        FulfillmentStatus $step,
        array $scenario,
        User $customer,
        User $staff,
        ?User $driver,
    ): OrderFulfillment {
        if ($fulfillment->status === $step) {
            return $fulfillment;
        }

        return match ($step) {
            FulfillmentStatus::Hold => $this->statuses->apply($fulfillment, $step, $staff, 'On hold while the delivery address is confirmed by phone'),
            FulfillmentStatus::Placed => $this->statuses->transition($fulfillment, $step, $staff, 'Confirmed with the customer by phone'),
            FulfillmentStatus::Processing => $this->statuses->transition($fulfillment, $step, $staff, 'Packaging started'),
            FulfillmentStatus::Ready => $this->statuses->markReady($fulfillment, $staff),
            FulfillmentStatus::Assigned => $this->assignDriver($fulfillment, $this->requireDriver($driver), $staff),
            FulfillmentStatus::PickedUp => $this->delivery->pickUp($fulfillment, $this->requireDriver($driver)),
            FulfillmentStatus::OutForDelivery => $this->delivery->startDelivery($fulfillment, $this->requireDriver($driver)),
            FulfillmentStatus::Arrived => $this->delivery->arrive($fulfillment, $this->requireDriver($driver)),
            FulfillmentStatus::Delivered => $this->delivery->complete(
                $fulfillment,
                $this->requireDriver($driver),
                (string) $this->placements->currentCode($fulfillment->order),
            ),
            FulfillmentStatus::DeliveryFailed => $this->delivery->fail(
                $fulfillment,
                $this->requireDriver($driver),
                DeliveryFailureReason::CustomerUnavailable,
                'No answer at the door and two calls went to voicemail.',
            ),
            FulfillmentStatus::Returning => $this->delivery->beginReturn($fulfillment, $this->requireDriver($driver)),
            FulfillmentStatus::Returned => $this->delivery->confirmReturn($fulfillment, $staff),
            FulfillmentStatus::Cancelled => ($scenario['cancelled_by'] ?? 'staff') === 'customer'
                ? $this->statuses->cancel($fulfillment, $customer, 'Cancelled by customer: ordered the wrong flavour')
                : $this->statuses->cancel($fulfillment, $staff, 'Cancelled by branch: customer asked to cancel by phone'),
            default => throw new RuntimeException('No seeder step for '.$step->value.'.'),
        };
    }

    /**
     * DriverAssignmentService::assign() only offers drivers whose shift covers
     * the current time, which would make seeding depend on the clock.
     */
    private function assignDriver(OrderFulfillment $fulfillment, User $driver, User $staff): OrderFulfillment
    {
        $fulfillment->forceFill(['driver_id' => $driver->id])->save();

        OrderDriverAssignmentHistory::query()->create([
            'order_id' => $fulfillment->order_id,
            'fulfillment_id' => $fulfillment->id,
            'from_driver_id' => null,
            'to_driver_id' => $driver->id,
            'reason' => null,
            'changed_by' => $staff->id,
        ]);

        $fulfillment = $this->statuses->apply($fulfillment->refresh(), FulfillmentStatus::Assigned, $staff, 'Driver assigned');
        $orderNumber = (string) $fulfillment->order()->value('order_number');

        $this->notifications->send(
            [$driver->id],
            NotificationType::Delivery,
            'New order assigned',
            'Order '.$orderNumber.' has been assigned to you.',
            ['order_number' => $orderNumber, 'fulfillment_id' => $fulfillment->id],
            $staff,
        );

        return $fulfillment;
    }

    private function requireDriver(?User $driver): User
    {
        return $driver ?? throw new RuntimeException('This order scenario needs a driver.');
    }

    /**
     * Spread the order and its history over the past so lists and timelines
     * read like real trading instead of everything landing at seed time.
     */
    private function backdate(Order $order, CarbonInterface $placedAt): void
    {
        $historyIds = OrderStatusHistory::query()
            ->where('order_id', $order->id)
            ->orderBy('id')
            ->pluck('id');
        $minutesAvailable = max(1, (int) $placedAt->diffInMinutes(now()));
        $gap = max(1, min(20, intdiv($minutesAvailable, max(1, $historyIds->count()))));
        $lastAt = $placedAt;

        foreach ($historyIds as $index => $historyId) {
            $lastAt = $placedAt->copy()->addMinutes($index * $gap);

            OrderStatusHistory::query()->whereKey($historyId)->update(['created_at' => $lastAt, 'updated_at' => $lastAt]);
        }

        $assignedAt = OrderStatusHistory::query()
            ->where('order_id', $order->id)
            ->where('new_status', FulfillmentStatus::Assigned->value)
            ->value('created_at');

        if ($assignedAt !== null) {
            OrderDriverAssignmentHistory::query()
                ->where('order_id', $order->id)
                ->update(['created_at' => $assignedAt, 'updated_at' => $assignedAt]);
        }

        Order::query()->whereKey($order->id)->update(['created_at' => $placedAt, 'updated_at' => $lastAt]);
        OrderFulfillment::query()->where('order_id', $order->id)->update(['created_at' => $placedAt, 'updated_at' => $lastAt]);
        OrderFulfillment::query()->where('order_id', $order->id)->whereNotNull('paid_at')->update(['paid_at' => $lastAt]);
        Payment::query()->where('order_id', $order->id)->update(['created_at' => $placedAt, 'updated_at' => $lastAt]);
    }
}
