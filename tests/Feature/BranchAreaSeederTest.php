<?php

use App\Models\Branch;
use App\Models\BranchArea;
use Database\Seeders\BranchAreaSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
});

test('branch area seeder creates zones for seeded branches and is idempotent', function () {
    $this->seed(BranchAreaSeeder::class);

    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    expect(
        BranchArea::query()->whereHas('branches', fn ($query) => $query->whereKey($sydney->id))->count(),
    )->toBe(3)
        ->and(
            BranchArea::query()->whereHas('branches', fn ($query) => $query->whereKey($melbourne->id))->count(),
        )->toBe(3)
        ->and(
            BranchArea::query()
                ->where('name', BranchAreaSeeder::SYDNEY_SURRY_HILLS)
                ->whereHas('branches', fn ($query) => $query->whereKey($sydney->id))
                ->exists(),
        )->toBeTrue();

    $sample = BranchArea::query()->where('name', BranchAreaSeeder::SYDNEY_SURRY_HILLS)->firstOrFail();

    expect($sample->polygon)->toBeArray()
        ->and(count($sample->polygon))->toBeGreaterThanOrEqual(4);

    $before = BranchArea::query()->count();

    $this->seed(BranchAreaSeeder::class);

    expect(BranchArea::query()->count())->toBe($before);
});
