<?php

namespace Database\Seeders;

use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverStockAssignmentMode;
use App\Models\Branch;
use App\Models\DriverInventory;
use App\Models\DriverStockAssignment;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Services\Driver\DriverStockAssignmentService;
use Database\Seeders\Concerns\ReportsSeedProgress;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Seed a courier starter bag onto every active driver via DriverStockAssignmentService.
 * Depends on UserSeeder + BranchInventorySeeder (and NationwideInventorySeeder if that was seeded).
 * Safe to re-run: skips branches where drivers already hold inventory or an assignment exists.
 */
class DriverStockAssignmentSeeder extends Seeder
{
    use ReportsSeedProgress;

    /**
     * Per-driver starter bag (product slug => quantity). Multiple mode deducts drivers × qty.
     *
     * @var array<string, int>
     */
    private const STARTER_BAG = [
        'marlboro-gold-soft-pack-20s' => 4,
        'winfield-blue-soft-pack-20s' => 4,
        'puffmemate-pulse-5000' => 3,
        'puffmemate-mini-bar-1200' => 2,
        'vuse-go-5000-disposable' => 3,
        'nordic-mint-pouches' => 3,
    ];

    public function run(): void
    {
        $actor = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->first();
        $service = app(DriverStockAssignmentService::class);
        $products = Product::query()
            ->whereIn('slug', array_keys(self::STARTER_BAG))
            ->where('status', true)
            ->get()
            ->keyBy('slug');

        $branches = Branch::query()
            ->where('status', true)
            ->whereHas(
                'driverBranchAssignments',
                fn ($query) => $query->where('status', DriverAssignmentStatus::Active),
            )
            ->with([
                'driverBranchAssignments' => fn ($query) => $query
                    ->where('status', DriverAssignmentStatus::Active)
                    ->with('driver:id,is_active'),
            ])
            ->orderBy('id')
            ->get();

        $this->withSeedProgress(
            'DriverBag',
            $branches,
            function (Branch $branch) use ($actor, $service, $products): void {
                $driverIds = $branch->driverBranchAssignments
                    ->pluck('driver')
                    ->filter(fn (?User $driver): bool => $driver !== null && $driver->is_active)
                    ->pluck('id')
                    ->unique()
                    ->values()
                    ->all();

                if ($driverIds === []) {
                    return;
                }

                if ($this->branchAlreadySeeded($branch->id, $driverIds)) {
                    return;
                }

                $items = $this->resolvableItems($branch, $products, count($driverIds));

                if ($items === []) {
                    return;
                }

                $service->assign(
                    $branch,
                    DriverStockAssignmentMode::Multiple,
                    $driverIds,
                    $items,
                    $actor,
                );
            },
            fn (): string => sprintf(
                '%d branch assignments on file (%d driver inventory rows).',
                DriverStockAssignment::query()->count(),
                DriverInventory::query()->count(),
            ),
        );
    }

    /**
     * @param  list<int>  $driverIds
     */
    private function branchAlreadySeeded(int $branchId, array $driverIds): bool
    {
        if (DriverStockAssignment::query()->where('branch_id', $branchId)->exists()) {
            return true;
        }

        return DriverInventory::query()
            ->where('branch_id', $branchId)
            ->whereIn('user_id', $driverIds)
            ->exists();
    }

    /**
     * @param  Collection<string, Product>  $products
     * @return list<array{product_id: int, variant_id: null, quantity: int}>
     */
    private function resolvableItems(Branch $branch, Collection $products, int $driverCount): array
    {
        $items = [];

        foreach (self::STARTER_BAG as $slug => $quantity) {
            $product = $products->get($slug);

            if ($product === null) {
                continue;
            }

            $inventory = Inventory::query()
                ->where('branch_id', $branch->id)
                ->where('product_id', $product->id)
                ->whereNull('variant_id')
                ->where('status', true)
                ->first(['quantity', 'reserved_quantity']);

            $available = $inventory === null
                ? 0
                : $inventory->quantity - $inventory->reserved_quantity;

            if ($available < $quantity * $driverCount) {
                continue;
            }

            $items[] = [
                'product_id' => $product->id,
                'variant_id' => null,
                'quantity' => $quantity,
            ];
        }

        return $items;
    }
}
