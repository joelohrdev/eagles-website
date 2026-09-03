<?php

use App\Services\ImageUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->uploader = app(ImageUploader::class);
});

test('store writes a webp full-size image and thumbnail', function () {
    $path = $this->uploader->store(UploadedFile::fake()->image('team.jpg', 2400, 1600), 'teams');

    expect($path)->toStartWith('teams/')->toEndWith('.webp');

    Storage::disk('public')->assertExists($path);
    Storage::disk('public')->assertExists(ImageUploader::thumbnailPath($path));

    [$width] = getimagesizefromstring(Storage::disk('public')->get($path));
    [$thumbWidth, $thumbHeight] = getimagesizefromstring(Storage::disk('public')->get(ImageUploader::thumbnailPath($path)));

    expect($width)->toBe(ImageUploader::MAX_WIDTH)
        ->and($thumbWidth)->toBe(ImageUploader::THUMB_WIDTH)
        ->and($thumbHeight)->toBe(ImageUploader::THUMB_HEIGHT);
});

test('replace deletes the previous image and thumbnail', function () {
    $old = $this->uploader->store(UploadedFile::fake()->image('a.jpg', 800, 600), 'teams');
    $new = $this->uploader->replace(UploadedFile::fake()->image('b.jpg', 800, 600), 'teams', $old);

    expect($new)->not->toBe($old);

    Storage::disk('public')->assertMissing($old);
    Storage::disk('public')->assertMissing(ImageUploader::thumbnailPath($old));
    Storage::disk('public')->assertExists($new);
});

test('delete removes both files and tolerates blank paths', function () {
    $path = $this->uploader->store(UploadedFile::fake()->image('a.jpg', 800, 600), 'coaches');

    $this->uploader->delete($path);
    $this->uploader->delete(null);

    Storage::disk('public')->assertMissing($path);
    Storage::disk('public')->assertMissing(ImageUploader::thumbnailPath($path));
});

test('share images are cropped to 1200x630 jpg', function () {
    $path = $this->uploader->storeShareImage(UploadedFile::fake()->image('share.png', 2000, 2000));

    expect($path)->toStartWith('share/')->toEndWith('.jpg');

    [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));

    expect($width)->toBe(ImageUploader::SHARE_WIDTH)
        ->and($height)->toBe(ImageUploader::SHARE_HEIGHT);
});

test('url helpers resolve public urls or null', function () {
    expect(ImageUploader::url(null))->toBeNull()
        ->and(ImageUploader::url('teams/x.webp'))->toEndWith('/storage/teams/x.webp')
        ->and(ImageUploader::thumbnailUrl('teams/x.webp'))->toEndWith('/storage/teams/thumbs/x.webp')
        ->and(ImageUploader::thumbnailPath('x.webp'))->toBe('thumbs/x.webp');
});

test('urls are host relative so they resolve against whatever host serves the app', function () {
    config()->set('app.url', 'http://not-the-host-in-use.test');

    expect(ImageUploader::url('teams/x.webp'))->toBe('/storage/teams/x.webp')
        ->and(ImageUploader::thumbnailUrl('teams/x.webp'))->toBe('/storage/teams/thumbs/x.webp');
});

test('absolute urls are fully qualified for off page consumers', function () {
    expect(ImageUploader::absoluteUrl(null))->toBeNull()
        ->and(ImageUploader::absoluteUrl('teams/x.webp'))
        ->toBe(url('/storage/teams/x.webp'))
        ->toStartWith('http');
});
