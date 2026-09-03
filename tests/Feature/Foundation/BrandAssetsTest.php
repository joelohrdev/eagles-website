<?php

test('the layout links the branded favicons', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<link rel="icon" href="/favicon.ico" sizes="16x16 32x32">', false)
        ->assertSee('<link rel="icon" href="/favicon.png" type="image/png" sizes="192x192">', false)
        ->assertSee('<link rel="apple-touch-icon" href="/apple-touch-icon.png">', false)
        ->assertDontSee('favicon.svg');
});

test('the branded icon and logo assets exist', function (string $file) {
    expect(public_path($file))->toBeFile();
})->with([
    'favicon.ico',
    'favicon.png',
    'apple-touch-icon.png',
    'eagles-logo.png',
]);
