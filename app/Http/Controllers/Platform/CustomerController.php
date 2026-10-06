<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\PlatformCustomerDataTable;
use App\Enums\PermissionEnum;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StorePlatformCustomerRequest;
use App\Http\Requests\Platform\UpdatePlatformCustomerRequest;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\User;
use App\Services\Platform\PlatformCustomerService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(private PlatformCustomerService $customers) {}

    public function index(Request $request): Response
    {
        return Inertia::render('platform/customers/index', [
            'table' => PlatformCustomerDataTable::make($request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('platform/customers/create');
    }

    public function store(StorePlatformCustomerRequest $request): RedirectResponse
    {
        $user = $this->customers->create(
            [
                'name' => $request->validated('name'),
                'phone' => $request->validated('phone'),
                'email' => $request->validated('email'),
                'password' => $request->validated('password'),
                'is_active' => $request->boolean('is_active', true),
                'shipping' => $request->validated('shipping'),
                'billing' => $request->validated('billing'),
            ],
            $request->file('avatar'),
        );

        Toast::success('Customer created.');

        return redirect()->route('platform.customers.show', $user);
    }

    public function show(Request $request, User $user): Response
    {
        $user = $this->resolveCustomer($user);
        $user->loadMissing(['shippingAddress', 'billingAddress']);

        $recentOrders = Order::query()
            ->where('customer_id', $user->id)
            ->latest('id')
            ->limit(8)
            ->get();

        return Inertia::render('platform/customers/show', [
            'customer' => $this->mapCustomer($user),
            'recentOrders' => $this->mapRecentOrders($recentOrders),
            'canViewOrders' => $request->user('platform')?->can(PermissionEnum::MANAGE_ORDERS->value) ?? false,
        ]);
    }

    public function edit(User $user): Response
    {
        $user = $this->resolveCustomer($user);
        $user->loadMissing(['shippingAddress', 'billingAddress']);

        return Inertia::render('platform/customers/edit', [
            'customer' => $this->mapCustomer($user),
        ]);
    }

    public function update(UpdatePlatformCustomerRequest $request, User $user): RedirectResponse
    {
        $user = $this->resolveCustomer($user);

        $this->customers->update(
            $user,
            [
                'name' => $request->validated('name'),
                'phone' => $request->validated('phone'),
                'email' => $request->validated('email'),
                'password' => $request->validated('password'),
                'is_active' => $request->boolean('is_active'),
                'remove_avatar' => $request->boolean('remove_avatar'),
                'shipping' => $request->validated('shipping'),
                'billing' => $request->validated('billing'),
            ],
            $request->file('avatar'),
        );

        Toast::success('Customer updated.');

        return redirect()->route('platform.customers.show', $user);
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $user = $this->resolveCustomer($user);
        $nextActive = ! $user->is_active;

        $this->customers->setActive($user, $nextActive);

        Toast::success($nextActive ? 'Customer activated.' : 'Customer deactivated.');

        return back();
    }

    private function resolveCustomer(User $user): User
    {
        if ($user->user_type !== UserType::CUSTOMER) {
            abort(404);
        }

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapCustomer(User $user): array
    {
        $status = $user->registration_status;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'avatar' => $user->hasMedia('avatar') ? 'avatar' : null,
            'avatar_url' => $user->avatarUrl(),
            'is_active' => (bool) $user->is_active,
            'registration_status' => $status?->value,
            'registration_status_label' => $status?->label() ?? '—',
            'phone_verified_at' => $user->phone_verified_at?->toIso8601String(),
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'address' => $this->formatShippingAddress($user->shippingAddress),
            'shipping' => $this->shippingPayload($user->shippingAddress),
            'billing' => $this->billingPayload($user->billingAddress),
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{address: string|null, city: string|null, region: string|null, zip: string|null}|null
     */
    private function shippingPayload(?CustomerAddress $address): ?array
    {
        if ($address === null) {
            return null;
        }

        return [
            'address' => $address->address,
            'city' => $address->city,
            'region' => $address->region?->value,
            'zip' => $address->zip,
        ];
    }

    /**
     * @return array{name: string|null, address: string|null, city: string|null, region: string|null, zip: string|null, email: string|null, phone: string|null}|null
     */
    private function billingPayload(?CustomerAddress $address): ?array
    {
        if ($address === null) {
            return null;
        }

        return [
            'name' => $address->name,
            'address' => $address->address,
            'city' => $address->city,
            'region' => $address->region?->value,
            'zip' => $address->zip,
            'email' => $address->email,
            'phone' => $address->phone,
        ];
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return list<array<string, mixed>>
     */
    private function mapRecentOrders(Collection $orders): array
    {
        return $orders->map(function (Order $order): array {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'payment_status_label' => $order->payment_status->label(),
                'total_label' => '$'.number_format((float) $order->total_amount, 2),
                'created_at' => $order->created_at?->toIso8601String(),
            ];
        })->values()->all();
    }

    private function formatShippingAddress(?CustomerAddress $address): ?string
    {
        if ($address === null) {
            return null;
        }

        $parts = array_values(array_filter([
            $address->address,
            $address->city,
            $address->region?->label() ?? $address->region?->value,
            $address->zip,
        ], fn (?string $part): bool => filled($part)));

        if ($parts === []) {
            return null;
        }

        return implode(', ', $parts);
    }
}
