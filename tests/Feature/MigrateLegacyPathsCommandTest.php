<?php

use App\Models\Branch;
use Illuminate\Support\Facades\Artisan;

it('attaches the storefront logo to branches missing logo media', function () {
    $logo = public_path('images/storefront/logo.png');

    if (! is_file($logo)) {
        $this->markTestSkipped('Committed storefront logo is missing.');
    }

    $branch = Branch::factory()->create();

    expect($branch->hasMedia('logo'))->toBeFalse();

    Artisan::call('media:migrate-legacy-paths');

    expect($branch->fresh()->hasMedia('logo'))->toBeTrue()
        ->and($branch->fresh()->getFirstMedia('logo')?->getCustomProperty('seed_source'))
        ->toBe('/images/storefront/logo.png');
});

it('is idempotent when logo media already exists', function () {
    $logo = public_path('images/storefront/logo.png');

    if (! is_file($logo)) {
        $this->markTestSkipped('Committed storefront logo is missing.');
    }

    $branch = Branch::factory()->create();

    Artisan::call('media:migrate-legacy-paths');
    Artisan::call('media:migrate-legacy-paths');

    expect($branch->fresh()->getMedia('logo'))->toHaveCount(1);
});
