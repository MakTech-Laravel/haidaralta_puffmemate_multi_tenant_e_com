<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\User;

it('imports every searchable model under app models', function () {
    $this->artisan('scout:import-all')
        ->expectsOutputToContain(User::class)
        ->expectsOutputToContain(Branch::class)
        ->expectsOutputToContain(Category::class)
        ->expectsOutputToContain(Product::class)
        ->expectsOutputToContain(Conversation::class)
        ->expectsOutputToContain('Imported 5 searchable model(s).')
        ->assertSuccessful();
});

it('accepts fresh and chunk options without failing', function () {
    $this->artisan('scout:import-all', [
        '--fresh' => true,
        '--chunk' => 50,
    ])->assertSuccessful();
});
