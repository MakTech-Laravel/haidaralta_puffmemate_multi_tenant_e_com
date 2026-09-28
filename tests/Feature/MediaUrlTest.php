<?php

use App\Support\MediaUrl;
use Illuminate\Support\Facades\Storage;

test('absolute http urls are returned unchanged', function () {
    expect(MediaUrl::resolve('https://cdn.example.com/a.jpg'))
        ->toBe('https://cdn.example.com/a.jpg')
        ->and(MediaUrl::resolve('http://cdn.example.com/b.png'))
        ->toBe('http://cdn.example.com/b.png');
});

test('existing public web paths are normalized with a leading slash', function () {
    expect(MediaUrl::resolve('images/storefront/logo.png'))
        ->toBe('/images/storefront/logo.png')
        ->and(MediaUrl::resolve('/images/storefront/logo.png'))
        ->toBe('/images/storefront/logo.png');
});

test('public files that are not real images fall back', function () {
    $relative = 'images/storefront/media-url-stub.jpg';
    $absolute = public_path($relative);
    file_put_contents($absolute, 'fake-image-bytes');

    try {
        expect(MediaUrl::isDisplayableFile($absolute))->toBeFalse()
            ->and(MediaUrl::resolve('/'.$relative))->toBe(MediaUrl::DEFAULT_FALLBACK);
    } finally {
        if (is_file($absolute)) {
            unlink($absolute);
        }
    }
});

test('existing public disk paths resolve to storage urls', function () {
    Storage::fake('public');
    Storage::disk('public')->put('products/demo.jpg', 'fake-image');

    expect(MediaUrl::resolve('products/demo.jpg'))
        ->toBe(Storage::disk('public')->url('products/demo.jpg'));
});

test('missing values use the default fallback', function () {
    expect(MediaUrl::resolve(null))->toBe(MediaUrl::DEFAULT_FALLBACK)
        ->and(MediaUrl::resolve(''))->toBe(MediaUrl::DEFAULT_FALLBACK)
        ->and(MediaUrl::resolve('products/missing-file.jpg'))->toBe(MediaUrl::DEFAULT_FALLBACK);
});

test('custom and null fallbacks are honored', function () {
    expect(MediaUrl::resolve(null, '/images/custom.png'))->toBe('/images/custom.png')
        ->and(MediaUrl::resolve(null, null))->toBeNull()
        ->and(MediaUrl::resolve('products/missing.jpg', null))->toBeNull();
});

test('resolveMany maps each value with the same fallback', function () {
    Storage::fake('public');
    Storage::disk('public')->put('products/a.jpg', 'a');

    expect(MediaUrl::resolveMany([
        'https://cdn.example.com/x.jpg',
        'products/a.jpg',
        'products/missing.jpg',
        null,
    ], '/fallback.png'))->toBe([
        'https://cdn.example.com/x.jpg',
        Storage::disk('public')->url('products/a.jpg'),
        '/fallback.png',
        '/fallback.png',
    ]);
});
