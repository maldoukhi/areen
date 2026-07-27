<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\OpenGraphImage;
use Symfony\Component\HttpFoundation\Response;

/**
 * The share card at `/og-image.png`.
 *
 * The URL carries a fingerprint of the club identity it was drawn from
 * ({@see OpenGraphImage::url()}), so it can be cached hard: a new logo or a
 * renamed club produces a new URL rather than a stale picture nobody can
 * flush out of a chat app's cache.
 *
 * If Chromium is not available on this host the shipped app icon is served
 * instead — a link that unfurls with the wrong picture is a great deal better
 * than one that unfurls with a broken image.
 */
class OpenGraphImageController extends Controller
{
    public function __invoke(): Response
    {
        $png = OpenGraphImage::bytes();

        if ($png !== null) {
            return response($png, 200, [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        }

        $fallback = public_path('brand/icon-512.png');

        abort_unless(is_file($fallback), 404);

        return response()->file($fallback, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
