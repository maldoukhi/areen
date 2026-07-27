<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Support\OpenGraphImage;
use App\Support\PdfRenderer;

it('serves a PNG for the share card', function (): void {
    $response = $this->get(route('seo.og-image'))->assertOk();

    expect($response->headers->get('Content-Type'))->toStartWith('image/png');
});

it('is a real 1200x630 card when Chromium is on this host', function (): void {
    $bytes = OpenGraphImage::bytes();

    expect($bytes)->not->toBeNull();

    $size = getimagesizefromstring($bytes);

    expect($size[0])->toBe(OpenGraphImage::WIDTH)
        ->and($size[1])->toBe(OpenGraphImage::HEIGHT);
})->skip(fn (): bool => ! PdfRenderer::isAvailable(), 'no Chromium on this host');

/*
 * The card is drawn from the settings row, so renaming the club has to change
 * the URL — a chat app caches an unfurl by URL and will never re-fetch the same
 * address.
 */
it('changes its URL when the club identity changes', function (): void {
    $setting = Setting::factory()->create(['club_name_ar' => 'قبل']);
    Setting::forget();

    $before = OpenGraphImage::url();

    $setting->update(['club_name_ar' => 'بعد']);
    Setting::forget();

    expect(OpenGraphImage::url())->not->toBe($before);
});

it('still answers with an image when Chromium is missing', function (): void {
    // Pointed at a path that cannot be a browser, which PdfRenderer treats as an
    // explicit instruction rather than one more guess — so no binary is found.
    putenv('CHROMIUM_PATH='.__FILE__);

    try {
        $response = $this->get(route('seo.og-image'))->assertOk();

        expect($response->headers->get('Content-Type'))->toStartWith('image/png');
    } finally {
        putenv('CHROMIUM_PATH');
    }
});
