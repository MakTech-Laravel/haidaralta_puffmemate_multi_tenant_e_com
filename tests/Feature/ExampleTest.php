<?php

use Inertia\Testing\AssertableInertia as Assert;

test('returns a successful response', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('storefront/home'));
});
