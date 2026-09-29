<?php

namespace App\Services\Catalog;

use App\Enums\InventoryTransactionType;
use App\Enums\PackOfferType;
use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Inventory;
use App\Models\Pack;
use App\Models\PackImage;
use App\Models\PackItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use App\Support\BranchProductPriceDisplay;
use App\Support\PackAvailability;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PackService
{
    public function __construct(
        private InventoryService $inventory,
        private PackAvailability $availability,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     description?: string|null,
     *     offer_type: string,
     *     offer_value: float|string,
     *     quantity: int,
     *     status?: bool,
     *     starts_at?: string|null,
     *     ends_at?: string|null,
     *     items: list<array{product_id: int, quantity: int}>,
     *     remove_image?: bool,
     *     remove_gallery_ids?: list<int>
     * }  $data
     * @param  list<UploadedFile>  $gallery
     */
    public function create(
        Branch $branch,
        array $data,
        ?UploadedFile $image,
        array $gallery,
        ?User $actor,
    ): Pack {
        return DB::transaction(function () use ($branch, $data, $image, $gallery, $actor): Pack {
            $items = $this->resolvedItems($branch, $data['items']);
            $quantity = (int) $data['quantity'];
            $this->assertStock($branch, $items, $quantity);

            $offerType = PackOfferType::from($data['offer_type']);
            $offerValue = $this->normalizedMoney($data['offer_value']);
            [$subtotal, $packPrice] = $this->pricedTotals($items, $offerType, $offerValue);
            $wantsActive = (bool) ($data['status'] ?? true);

            $pack = Pack::query()->create([
                'branch_id' => $branch->id,
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($branch, $data['name']),
                'description' => $data['description'] ?? null,
                'image' => $image?->store('packs', 'public'),
                'offer_type' => $offerType,
                'offer_value' => $offerValue,
                'subtotal' => $subtotal,
                'pack_price' => $packPrice,
                'quantity' => $quantity,
                'status' => $wantsActive,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
            ]);

            foreach ($items as $item) {
                PackItem::query()->create([
                    'pack_id' => $pack->id,
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                ]);
            }

            $this->appendGallery($pack, $gallery);
            $this->allocate($branch, $pack->fresh(['items.product']) ?? $pack, $quantity, $actor);
            $this->persistStatus($pack, $wantsActive && $quantity > 0);

            return $pack->refresh()->load(['items.product', 'images']);
        });
    }

    /**
     * @param  array{
     *     name: string,
     *     description?: string|null,
     *     offer_type: string,
     *     offer_value: float|string,
     *     quantity: int,
     *     status?: bool,
     *     starts_at?: string|null,
     *     ends_at?: string|null,
     *     remove_image?: bool,
     *     remove_gallery_ids?: list<int>
     * }  $data
     * @param  list<UploadedFile>  $gallery
     */
    public function update(
        Pack $pack,
        array $data,
        ?UploadedFile $image,
        array $gallery,
        ?User $actor,
    ): Pack {
        return DB::transaction(function () use ($pack, $data, $image, $gallery, $actor): Pack {
            $pack->load(['items.product', 'images', 'branch']);
            $branch = $pack->branch;
            $quantity = (int) $data['quantity'];
            $delta = $quantity - $pack->quantity;
            $currentlyActive = $pack->status;
            $wantsActive = (bool) ($data['status'] ?? $pack->status);

            if ($delta > 0) {
                $this->assertStock($branch, $this->itemsFromPack($pack), $delta);
            }

            $offerType = PackOfferType::from($data['offer_type']);
            $offerValue = $this->normalizedMoney($data['offer_value']);
            [$subtotal, $packPrice] = $this->pricedTotals($this->itemsFromPack($pack), $offerType, $offerValue);

            if (($data['remove_image'] ?? false) === true && $pack->image) {
                Storage::disk('public')->delete($pack->image);
                $pack->image = null;
            }

            if ($image !== null) {
                if ($pack->image) {
                    Storage::disk('public')->delete($pack->image);
                }
                $pack->image = $image->store('packs', 'public');
            }

            $this->deleteGallery($pack, $data['remove_gallery_ids'] ?? []);
            $this->appendGallery($pack, $gallery);

            if ($delta > 0) {
                $this->allocate($branch, $pack, $delta, $actor);
            } elseif ($delta < 0) {
                $this->release($branch, $pack, abs($delta), $actor);
            }

            $status = false;

            if ($wantsActive && $quantity > 0) {
                if ($currentlyActive || $delta > 0) {
                    $status = true;
                } elseif (! $this->availability->canActivate($pack)) {
                    throw ValidationException::withMessages([
                        'status' => 'This pack cannot be active until every product has enough remaining stock.',
                    ]);
                } else {
                    $status = true;
                }
            }

            $pack->fill([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($branch, $data['name'], $pack->id),
                'description' => $data['description'] ?? null,
                'offer_type' => $offerType,
                'offer_value' => $offerValue,
                'subtotal' => $subtotal,
                'pack_price' => $packPrice,
                'quantity' => $quantity,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
            ]);

            $pack->save();
            $this->persistStatus($pack, $status);

            return $pack->refresh()->load(['items.product', 'images']);
        });
    }

    public function delete(Pack $pack, ?User $actor): void
    {
        DB::transaction(function () use ($pack, $actor): void {
            $pack->load(['items.product', 'images', 'branch']);

            if ($pack->quantity > 0) {
                $this->release($pack->branch, $pack, $pack->quantity, $actor);
            }

            if ($pack->image) {
                Storage::disk('public')->delete($pack->image);
            }

            foreach ($pack->images as $image) {
                Storage::disk('public')->delete($image->path);
            }

            $pack->delete();
        });
    }

    /**
     * @param  list<array{product_id: int, quantity: int}>  $rawItems
     * @return list<array{product: Product, quantity: int, unit_price: float}>
     */
    private function resolvedItems(Branch $branch, array $rawItems): array
    {
        $ids = collect($rawItems)->pluck('product_id')->map(fn ($id): int => (int) $id)->unique()->values();

        $products = Product::query()
            ->whereIn('id', $ids)
            ->where('status', true)
            ->get()
            ->keyBy('id');

        $stocked = Inventory::query()
            ->where('branch_id', $branch->id)
            ->whereIn('product_id', $ids)
            ->whereNull('variant_id')
            ->pluck('product_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $resolved = [];

        foreach ($rawItems as $index => $raw) {
            $productId = (int) $raw['product_id'];
            $product = $products->get($productId);

            if ($product === null || ! in_array($productId, $stocked, true)) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'Choose a stocked product from this branch.',
                ]);
            }

            $resolved[] = [
                'product' => $product,
                'quantity' => max(1, (int) $raw['quantity']),
                'unit_price' => $this->unitPrice($branch, $product),
            ];
        }

        return $resolved;
    }

    /**
     * @return list<array{product: Product, quantity: int, unit_price: float}>
     */
    private function itemsFromPack(Pack $pack): array
    {
        return $pack->items
            ->map(function (PackItem $item) use ($pack): array {
                /** @var Product $product */
                $product = $item->product;

                return [
                    'product' => $product,
                    'quantity' => $item->quantity,
                    'unit_price' => $this->unitPrice($pack->branch, $product),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array{product: Product, quantity: int, unit_price: float}>  $items
     */
    private function assertStock(Branch $branch, array $items, int $packQuantity): void
    {
        $messages = [];

        foreach ($items as $index => $item) {
            $needed = $item['quantity'] * $packQuantity;
            $onHand = $this->availability->remainingOnHand($branch->id, $item['product']->id);

            if ($onHand < $needed) {
                $messages["items.{$index}.quantity"] = $onHand <= 0
                    ? 'Out of stock. '.$item['product']->name.' has no stock available.'
                    : 'Not enough stock. '.$item['product']->name.' needs '.$needed.' units but only '.$onHand.' available.';
            }
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * @param  list<array{product: Product, quantity: int, unit_price: float}>  $items
     * @return array{0: string, 1: string}
     */
    private function pricedTotals(array $items, PackOfferType $offerType, string $offerValue): array
    {
        $subtotal = 0.0;

        foreach ($items as $item) {
            $subtotal += $item['unit_price'] * $item['quantity'];
        }

        $offer = (float) $offerValue;

        $packPrice = match ($offerType) {
            PackOfferType::Fixed => max(0, $subtotal - max($offer, 0)),
            PackOfferType::Percent => max(0, $subtotal * (1 - min(max($offer, 0), 100) / 100)),
            PackOfferType::PackPrice => max($offer, 0),
        };

        return [
            number_format($subtotal, 2, '.', ''),
            number_format($packPrice, 2, '.', ''),
        ];
    }

    private function unitPrice(Branch $branch, Product $product): float
    {
        $price = null;

        if (BranchProductPriceDisplay::usesBranchPricing()) {
            $price = BranchProductPrice::query()
                ->where('branch_id', $branch->id)
                ->where('product_id', $product->id)
                ->whereNull('variant_id')
                ->where('status', true)
                ->first();
        }

        $display = BranchProductPriceDisplay::listDisplayForProductContext($price, $product);
        $effective = $display['effective_price'] ?? $display['selling_price'];

        return is_numeric($effective) ? (float) $effective : 0.0;
    }

    private function allocate(Branch $branch, Pack $pack, int $packQuantity, ?User $actor): void
    {
        foreach ($pack->items->values() as $index => $item) {
            $this->adjustItem($branch, $pack, $item, $index, $packQuantity, InventoryTransactionType::Reserve, $actor);
        }
    }

    private function release(Branch $branch, Pack $pack, int $packQuantity, ?User $actor): void
    {
        foreach ($pack->items->values() as $index => $item) {
            $this->adjustItem($branch, $pack, $item, $index, $packQuantity, InventoryTransactionType::Release, $actor);
        }
    }

    private function adjustItem(
        Branch $branch,
        Pack $pack,
        PackItem $item,
        int $index,
        int $packQuantity,
        InventoryTransactionType $type,
        ?User $actor,
    ): void {
        $product = $item->product;

        if ($product === null) {
            return;
        }

        $inventory = $this->inventory->firstOrCreate($branch, $product);
        $units = $item->quantity * $packQuantity;

        if ($units < 1) {
            return;
        }

        try {
            $this->inventory->adjust(
                $inventory,
                $type,
                $units,
                $actor,
                Pack::class,
                $pack->id,
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                "items.{$index}.quantity" => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  list<UploadedFile>  $gallery
     */
    private function appendGallery(Pack $pack, array $gallery): void
    {
        $nextSort = (int) $pack->images()->max('sort_order');

        foreach ($gallery as $file) {
            $nextSort++;
            PackImage::query()->create([
                'pack_id' => $pack->id,
                'path' => $file->store('packs/gallery', 'public'),
                'sort_order' => $nextSort,
            ]);
        }
    }

    /**
     * @param  list<int>  $imageIds
     */
    private function deleteGallery(Pack $pack, array $imageIds): void
    {
        if ($imageIds === []) {
            return;
        }

        $images = PackImage::query()
            ->where('pack_id', $pack->id)
            ->whereIn('id', $imageIds)
            ->get();

        foreach ($images as $image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
        }
    }

    private function persistStatus(Pack $pack, bool $status): void
    {
        Pack::query()->whereKey($pack->id)->update(['status' => $status]);
        $pack->setAttribute('status', $status);
    }

    private function uniqueSlug(Branch $branch, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $base = $base === '' ? 'pack' : $base;
        $slug = $base;
        $suffix = 2;

        while (
            Pack::query()
                ->where('branch_id', $branch->id)
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function normalizedMoney(float|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
