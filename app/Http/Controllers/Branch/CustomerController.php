<?php

namespace App\Http\Controllers\Branch;

use App\DataTables\BranchCustomerDataTable;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request, Branch $branch): Response
    {
        return Inertia::render('branch/customers/index', [
            'branch' => $branch->toPortalContext(),
            'table' => BranchCustomerDataTable::makeForBranch($request, $branch),
        ]);
    }

    public function show(Branch $branch, User $user): Response
    {
        $user = $this->resolveBranchCustomer($branch, $user);
        $user->loadMissing(['shippingAddress', 'billingAddress']);

        $recentOrders = Order::query()
            ->where('customer_id', $user->id)
            ->whereHas(
                'fulfillments',
                fn ($query) => $query->where('branch_id', $branch->id),
            )
            ->with([
                'fulfillments' => fn ($query) => $query->where('branch_id', $branch->id),
            ])
            ->latest('id')
            ->limit(8)
            ->get();

        return Inertia::render('branch/customers/show', [
            'branch' => $branch->toPortalContext(),
            'customer' => $this->mapCustomer($user),
            'recentOrders' => $this->mapRecentOrders($recentOrders),
        ]);
    }

    private function resolveBranchCustomer(Branch $branch, User $user): User
    {
        abort_unless($user->user_type === UserType::CUSTOMER, 404);

        abort_unless(
            $user->orders()
                ->whereHas(
                    'fulfillments',
                    fn ($query) => $query->where('branch_id', $branch->id),
                )
                ->exists(),
            404,
        );

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
            $fulfillment = $order->fulfillments->first();

            return [
                'id' => $order->id,
                'fulfillmentId' => $fulfillment?->id,
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
