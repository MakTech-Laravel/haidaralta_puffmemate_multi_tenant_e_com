<?php

namespace App\DataTables;

use App\DataTables\Filters\StatusBooleanFilter;
use App\Enums\CustomerRegistrationStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * @extends AbstractDataTable<User>
 */
class BranchCustomerDataTable extends AbstractDataTable
{
    public function __construct(
        Request $request,
        protected Branch $branch,
    ) {
        parent::__construct($request);
    }

    /**
     * @return array<string, mixed>
     */
    public static function makeForBranch(Request $request, Branch $branch): array
    {
        return (new static($request, $branch))->toArray();
    }

    /**
     * @return class-string<User>
     */
    protected function modelClass(): string
    {
        return User::class;
    }

    /**
     * @return Builder<User>
     */
    protected function baseQuery(): Builder
    {
        $branchId = $this->branch->id;

        return User::query()
            ->where('user_type', UserType::CUSTOMER)
            ->whereHas(
                'orders.fulfillments',
                fn (Builder $fulfillments): Builder => $fulfillments->where('branch_id', $branchId),
            )
            ->with(['shippingAddress'])
            ->withCount([
                'orders as orders_at_branch_count' => fn (Builder $orders): Builder => $orders->whereHas(
                    'fulfillments',
                    fn (Builder $fulfillments): Builder => $fulfillments->where('branch_id', $branchId),
                ),
            ])
            ->withMax([
                'orders as last_order_at_branch' => fn (Builder $orders): Builder => $orders->whereHas(
                    'fulfillments',
                    fn (Builder $fulfillments): Builder => $fulfillments->where('branch_id', $branchId),
                ),
            ], 'created_at');
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::exact('registration_status'),
            StatusBooleanFilter::make('is_active'),
            AllowedFilter::callback('phone_verified', function (Builder $query, mixed $value): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                $normalized = strtolower((string) $value);

                if (in_array($normalized, ['1', 'true', 'verified', 'yes'], true)) {
                    $query->whereNotNull('phone_verified_at');

                    return;
                }

                if (in_array($normalized, ['0', 'false', 'unverified', 'no'], true)) {
                    $query->whereNull('phone_verified_at');
                }
            }),
            $this->searchFilter(),
        ];
    }

    /**
     * @return list<string>
     */
    protected function modelSearchableColumns(): array
    {
        return ['name', 'phone', 'email'];
    }

    /**
     * @return list<string>
     */
    protected function allowedSorts(): array
    {
        return [
            'name',
            'created_at',
        ];
    }

    protected function defaultSort(): string
    {
        return '-created_at';
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterOptions(): array
    {
        return [
            'registration_status' => CustomerRegistrationStatus::options(),
        ];
    }

    /**
     * @param  User  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var User $model */
        $status = $model->registration_status;

        return [
            'id' => $model->id,
            'name' => $model->name,
            'phone' => $model->phone,
            'avatar_url' => $model->avatarUrl(),
            'registration_status' => $status?->value,
            'registration_status_label' => $status?->label() ?? '—',
            'phone_verified' => $model->phone_verified_at !== null,
            'address' => $this->formatShippingAddress($model->shippingAddress),
            'is_active' => (bool) $model->is_active,
            'orders_at_branch_count' => (int) ($model->orders_at_branch_count ?? 0),
            'last_order_at_branch' => $model->last_order_at_branch
                ? Carbon::parse($model->last_order_at_branch)->toIso8601String()
                : null,
            'created_at' => $model->created_at?->toIso8601String(),
        ];
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
