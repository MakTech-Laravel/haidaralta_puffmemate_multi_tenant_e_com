<?php

namespace Database\Seeders;

use App\Enums\DiscountType;
use App\Enums\InventoryTransactionType;
use App\Models\Branch;
use App\Models\Product;
use Carbon\CarbonInterface;
use Database\Seeders\Concerns\ReportsSeedProgress;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NationwideInventorySeeder extends Seeder
{
    use ReportsSeedProgress;
    use WithoutModelEvents;

    private const CHUNK_SIZE = 200;

    /**
     * @var list<array<string, mixed>>
     */
    private array $inventoryRows = [];

    /**
     * @var list<array<string, mixed>>
     */
    private array $transactionRows = [];

    /**
     * @var list<array<string, mixed>>
     */
    private array $priceRows = [];

    private int $stockedRows = 0;

    private int $outOfStockRows = 0;

    private int $unitsStocked = 0;

    /**
     * Stock every active product across the whole network with a realistic, uneven assortment:
     * core stores carry ~85% of the range, metro ~60%, regional ~40%, remote ~25%. Around 8% of
     * listed lines sit at zero and ~12% are low stock so allocation has to split or transfer.
     *
     * Existing product-level rows (BranchInventorySeeder's hand-tuned stock) are never touched,
     * so the seeder is safe to re-run. Rows are bulk inserted in chunks for speed.
     */
    public function run(): void
    {
        $branches = Branch::query()->where('status', true)->orderBy('id')->get(['id', 'slug', 'delivery_radius_km']);
        $products = Product::query()->where('status', true)->orderBy('id')->get(['id', 'slug', 'selling_price']);
        $existingStock = $this->existingKeys('inventories');
        $existingPrices = $this->existingKeys('branch_product_prices');
        $now = now();

        $this->startSeedProgress('Stock', $branches->count());

        foreach ($branches as $branch) {
            $coverage = $this->coverageFor($branch->delivery_radius_km);
            $priceUplift = $this->priceUpliftFor($branch->delivery_radius_km);

            foreach ($products as $product) {
                $hash = crc32($branch->slug.'|'.$product->slug);

                if ($hash % 100 >= $coverage) {
                    continue;
                }

                $key = $branch->id.':'.$product->id;

                if (! isset($existingStock[$key])) {
                    $this->queueStock($branch, $product, $this->quantityFor($hash, $branch->delivery_radius_km), $now);
                }

                if (! isset($existingPrices[$key]) && $product->selling_price !== null) {
                    $this->queuePrice($branch, $product, $hash, $priceUplift, $now);
                }
            }

            $this->flush();
            $this->advanceSeedProgress();
        }

        $this->flush(force: true);

        $this->finishSeedProgress(sprintf(
            '%s stock lines added (%s out of stock), %s units across %d branches and %d products.',
            number_format($this->stockedRows),
            number_format($this->outOfStockRows),
            number_format($this->unitsStocked),
            $branches->count(),
            $products->count(),
        ));
    }

    /**
     * @return Collection<string, bool>
     */
    private function existingKeys(string $table): Collection
    {
        return DB::table($table)
            ->whereNull('variant_id')
            ->get(['branch_id', 'product_id'])
            ->mapWithKeys(fn (object $row): array => [$row->branch_id.':'.$row->product_id => true]);
    }

    private function coverageFor(?string $radiusKm): int
    {
        return match (true) {
            $radiusKm === null => 85,
            (float) $radiusKm <= NationwideBranchSeeder::RADIUS_METRO_KM => 60,
            (float) $radiusKm <= NationwideBranchSeeder::RADIUS_REGIONAL_KM => 40,
            default => 25,
        };
    }

    private function priceUpliftFor(?string $radiusKm): float
    {
        return match (true) {
            $radiusKm === null, (float) $radiusKm <= NationwideBranchSeeder::RADIUS_METRO_KM => 1.00,
            (float) $radiusKm <= NationwideBranchSeeder::RADIUS_REGIONAL_KM => 1.03,
            default => 1.08,
        };
    }

    private function quantityFor(int $hash, ?string $radiusKm): int
    {
        $roll = intdiv($hash, 100) % 100;

        if ($roll < 8) {
            return 0;
        }

        if ($roll < 20) {
            return 1 + ($roll % 3);
        }

        [$min, $max] = match (true) {
            $radiusKm === null => [20, 120],
            (float) $radiusKm <= NationwideBranchSeeder::RADIUS_METRO_KM => [10, 60],
            (float) $radiusKm <= NationwideBranchSeeder::RADIUS_REGIONAL_KM => [5, 35],
            default => [3, 20],
        };

        return $min + (intdiv($hash, 10000) % ($max - $min + 1));
    }

    private function queueStock(Branch $branch, Product $product, int $quantity, CarbonInterface $now): void
    {
        $this->inventoryRows[] = [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => $quantity,
            'reserved_quantity' => 0,
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->stockedRows++;

        if ($quantity === 0) {
            $this->outOfStockRows++;

            return;
        }

        $this->unitsStocked += $quantity;

        $this->transactionRows[] = [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'variant_id' => null,
            'type' => InventoryTransactionType::StockIn->value,
            'reason' => null,
            'reason_note' => null,
            'quantity' => $quantity,
            'before_quantity' => 0,
            'after_quantity' => $quantity,
            'unit_cost' => $this->money((float) $product->selling_price * 0.62),
            'reference_type' => null,
            'reference_id' => null,
            'created_by' => null,
            'created_at' => $now,
        ];
    }

    private function queuePrice(Branch $branch, Product $product, int $hash, float $uplift, CarbonInterface $now): void
    {
        $selling = round((float) $product->selling_price * $uplift, 2);
        $onOffer = $hash % 13 === 0;

        $this->priceRows[] = [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'variant_id' => null,
            'buying_price' => $this->money($selling * 0.62),
            'regular_price' => $this->money($selling * 1.15),
            'selling_price' => $this->money($selling),
            'discount_type' => $onOffer ? DiscountType::Fixed->value : DiscountType::None->value,
            'discount_price' => $onOffer ? $this->money(max(0.5, round($selling * 0.08, 1))) : null,
            'start_date' => null,
            'end_date' => null,
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function flush(bool $force = false): void
    {
        foreach (['inventories' => 'inventoryRows', 'inventory_transactions' => 'transactionRows', 'branch_product_prices' => 'priceRows'] as $table => $buffer) {
            if (! $force && count($this->{$buffer}) < self::CHUNK_SIZE) {
                continue;
            }

            foreach (array_chunk($this->{$buffer}, self::CHUNK_SIZE) as $chunk) {
                DB::table($table)->insert($chunk);
            }

            $this->{$buffer} = [];
        }
    }

    private function money(float $amount): string
    {
        return number_format(round($amount, 2), 2, '.', '');
    }
}
