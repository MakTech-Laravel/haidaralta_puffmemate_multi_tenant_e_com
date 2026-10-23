<?php

namespace App\Services\Branch;

use App\Models\Branch;
use App\Models\BranchArea;
use App\Models\BranchAreaTransferHistory;
use App\Models\User;
use App\Support\BranchMap;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BranchAreaService
{
    /**
     * @param  array{
     *     name: string,
     *     address: string,
     *     latitude: float|string,
     *     longitude: float|string,
     *     city: string,
     *     state: string,
     *     postal_code?: string|null,
     *     notes?: string|null,
     *     is_active?: bool
     * }  $data
     */
    public function create(Branch $branch, array $data): BranchArea
    {
        return DB::transaction(function () use ($branch, $data): BranchArea {
            $area = BranchArea::query()->create($this->normalizedAttributes($data));
            $area->branches()->attach($branch->id);

            return $area;
        });
    }

    /**
     * @param  array{
     *     name: string,
     *     address: string,
     *     latitude: float|string,
     *     longitude: float|string,
     *     city: string,
     *     state: string,
     *     postal_code?: string|null,
     *     notes?: string|null,
     *     is_active?: bool
     * }  $data
     */
    public function update(BranchArea $area, array $data): BranchArea
    {
        $area->update($this->normalizedAttributes($data));

        return $area->refresh();
    }

    public function delete(BranchArea $area): void
    {
        $area->delete();
    }

    public function detach(Branch $branch, BranchArea $area): void
    {
        if (! $area->branches()->whereKey($branch->id)->exists()) {
            throw ValidationException::withMessages([
                'branch_id' => 'This area is not linked to that branch.',
            ]);
        }

        $area->branches()->detach($branch->id);
    }

    /**
     * Attach an area to another branch without removing existing memberships.
     */
    public function attach(
        BranchArea $area,
        Branch $to,
        Carbon|string $effectiveDate,
        string $reason,
        User $actor,
    ): BranchAreaTransferHistory {
        if ($area->branches()->whereKey($to->id)->exists()) {
            throw ValidationException::withMessages([
                'to_branch_id' => 'This area is already linked to that branch.',
            ]);
        }

        if (! $to->status) {
            throw ValidationException::withMessages([
                'to_branch_id' => 'Cannot add an area to an inactive branch.',
            ]);
        }

        $fromBranchId = $area->branches()->orderBy('name')->value('branches.id');

        if ($fromBranchId === null) {
            $fromBranchId = $to->id;
        }

        $effective = $effectiveDate instanceof Carbon
            ? $effectiveDate->copy()->startOfDay()
            : Carbon::parse($effectiveDate)->startOfDay();

        return DB::transaction(function () use ($area, $to, $fromBranchId, $effective, $reason, $actor): BranchAreaTransferHistory {
            $area->branches()->attach($to->id);

            return BranchAreaTransferHistory::query()->create([
                'branch_area_id' => $area->id,
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $to->id,
                'effective_date' => $effective->toDateString(),
                'reason' => trim($reason),
                'transferred_by' => $actor->id,
            ]);
        });
    }

    /**
     * @deprecated Use attach() — kept for call-site clarity during rename.
     */
    public function transfer(
        BranchArea $area,
        Branch $to,
        Carbon|string $effectiveDate,
        string $reason,
        User $actor,
    ): BranchAreaTransferHistory {
        return $this->attach($area, $to, $effectiveDate, $reason, $actor);
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     address: string,
     *     latitude: string,
     *     longitude: string,
     *     map: ?array{google: string, open_street_map: string, embed: string},
     *     city: string,
     *     state: string,
     *     postal_code: ?string,
     *     notes: ?string,
     *     is_active: bool,
     *     created_at: ?string,
     *     branches: list<array{id: int, name: string, slug: string}>,
     *     transfers: list<array{
     *         id: int,
     *         from_branch_id: int,
     *         from_branch_name: ?string,
     *         to_branch_id: int,
     *         to_branch_name: ?string,
     *         effective_date: ?string,
     *         reason: string,
     *         transferred_by_name: ?string,
     *         transfer_type_label: string
     *     }>
     * }
     */
    public function detail(BranchArea $area): array
    {
        $area->loadMissing([
            'branches:id,name,slug',
            'transferHistories.fromBranch:id,name',
            'transferHistories.toBranch:id,name',
            'transferHistories.transferredBy:id,name',
        ]);

        return [
            'id' => $area->id,
            'name' => $area->name,
            'address' => $area->address,
            'latitude' => (string) $area->latitude,
            'longitude' => (string) $area->longitude,
            'map' => BranchMap::links($area->latitude, $area->longitude),
            'city' => $area->city,
            'state' => $area->state,
            'postal_code' => $area->postal_code,
            'notes' => $area->notes,
            'is_active' => $area->is_active,
            'created_at' => $area->created_at?->toIso8601String(),
            'branches' => $area->branches
                ->sortBy('name')
                ->values()
                ->map(fn (Branch $branch): array => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'slug' => $branch->slug,
                ])
                ->all(),
            'transfers' => $area->transferHistories
                ->map(fn (BranchAreaTransferHistory $transfer): array => [
                    'id' => $transfer->id,
                    'from_branch_id' => $transfer->from_branch_id,
                    'from_branch_name' => $transfer->fromBranch?->name,
                    'to_branch_id' => $transfer->to_branch_id,
                    'to_branch_name' => $transfer->toBranch?->name,
                    'effective_date' => $transfer->effective_date?->toDateString(),
                    'reason' => $transfer->reason,
                    'transferred_by_name' => $transfer->transferredBy?->name,
                    'transfer_type_label' => 'Added to branch',
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     name: string,
     *     address: string,
     *     latitude: string,
     *     longitude: string,
     *     city: string,
     *     state: string,
     *     postal_code: ?string,
     *     notes: ?string,
     *     is_active: bool
     * }
     */
    private function normalizedAttributes(array $data): array
    {
        return [
            'name' => trim((string) $data['name']),
            'address' => trim((string) $data['address']),
            'latitude' => number_format((float) $data['latitude'], 7, '.', ''),
            'longitude' => number_format((float) $data['longitude'], 7, '.', ''),
            'city' => trim((string) $data['city']),
            'state' => trim((string) $data['state']),
            'postal_code' => filled($data['postal_code'] ?? null)
                ? trim((string) $data['postal_code'])
                : null,
            'notes' => filled($data['notes'] ?? null)
                ? trim((string) $data['notes'])
                : null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
    }
}
