<?php

use App\Models\User;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    Storage::fake('public');
});

it('stores raster uploads as webp on a media collection', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('avatar.png', 120, 120);

    $media = app(MediaUploadService::class)->syncSingle($user, 'avatar', $file);

    expect($media)->toBeInstanceOf(Media::class)
        ->and($media->mime_type)->toBe('image/webp')
        ->and($media->extension)->toBe('webp')
        ->and($user->fresh()->getFirstMedia('avatar'))->not->toBeNull();
});

it('keeps svg uploads as svg', function () {
    $user = User::factory()->create();
    $svg = UploadedFile::fake()->createWithContent(
        'icon.svg',
        '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10"/></svg>',
    );

    $media = app(MediaUploadService::class)->syncSingle($user, 'avatar', $svg);

    expect($media->extension)->toBe('svg')
        ->and(str_contains((string) $media->mime_type, 'svg'))->toBeTrue();
});

it('clears a single-file collection when remove is true', function () {
    $user = User::factory()->create();
    $service = app(MediaUploadService::class);

    $service->syncSingle($user, 'avatar', UploadedFile::fake()->image('a.jpg'));
    expect($user->fresh()->getMedia('avatar'))->toHaveCount(1);

    $service->syncSingle($user, 'avatar', null, remove: true);

    expect($user->fresh()->getMedia('avatar'))->toHaveCount(0);
});
