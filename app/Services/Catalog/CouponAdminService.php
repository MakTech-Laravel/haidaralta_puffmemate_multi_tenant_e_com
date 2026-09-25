<?php

namespace App\Services\Catalog;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CouponAdminService
{
    /**
     * @return Collection<int, Coupon>
     */
    public function all(): Collection
    {
        return Coupon::query()->orderByDesc('id')->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function indexPayload(): array
    {
        return $this->all()
            ->map(fn (Coupon $coupon): array => $this->row($coupon))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function formPayload(Coupon $coupon): array
    {
        return $this->row($coupon);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Coupon
    {
        return Coupon::query()->create($this->normalize($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Coupon $coupon, array $data): Coupon
    {
        $coupon->fill($this->normalize($data))->save();

        return $coupon->refresh();
    }

    public function delete(Coupon $coupon): void
    {
        $coupon->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Coupon $coupon): array
    {
        return [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'type' => $coupon->type->value,
            'type_label' => $coupon->type->label(),
            'value' => (string) $coupon->value,
            'min_subtotal' => $coupon->min_subtotal === null ? null : (string) $coupon->min_subtotal,
            'max_discount' => $coupon->max_discount === null ? null : (string) $coupon->max_discount,
            'starts_at' => $coupon->starts_at?->toDateString(),
            'ends_at' => $coupon->ends_at?->toDateString(),
            'usage_limit' => $coupon->usage_limit,
            'usage_limit_per_customer' => $coupon->usage_limit_per_customer,
            'status' => $coupon->status,
            'redemptions' => $coupon->redemptions()->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        return [
            'code' => Str::upper(trim((string) $data['code'])),
            'type' => CouponType::from((string) $data['type']),
            'value' => $data['value'],
            'min_subtotal' => $data['min_subtotal'] ?? null,
            'max_discount' => $data['max_discount'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'usage_limit' => $data['usage_limit'] ?? null,
            'usage_limit_per_customer' => $data['usage_limit_per_customer'] ?? null,
            'status' => $data['status'] ?? true,
        ];
    }
}
