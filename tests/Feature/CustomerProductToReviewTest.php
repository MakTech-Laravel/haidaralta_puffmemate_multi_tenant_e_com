<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from product to review', function () {
    $this->get(route('customer.product-to-review.index'))
        ->assertRedirect(route('login'));
});

test('customers can view the demo product to review page', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'customer');

    $this->get(route('customer.product-to-review.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/product-to-review/index')
            ->has('products', 13)
            ->where('products.0.name', 'Marlboro Gold')
            ->where('products.0.shippingStatus', 'delivered'));
});

test('guests are redirected from write product review', function () {
    $this->get(route('customer.product-to-review.show', 'review-1'))
        ->assertRedirect(route('login'));
});

test('customers can view the write product review page', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'customer');

    $this->get(route('customer.product-to-review.show', 'review-1'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/product-to-review/show')
            ->where('product.id', 'review-1')
            ->where('product.name', 'Marlboro Gold'));
});

test('customers receive not found for unknown products to review', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'customer');

    $this->get(route('customer.product-to-review.show', 'review-missing'))
        ->assertNotFound();
});
