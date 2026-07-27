<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * `robots.txt`, served by Laravel rather than shipped as a static file, so the
 * `Sitemap:` line always names the host the app is actually running on instead
 * of a domain written into the repository.
 *
 * Everything that is not the public catalogue is closed. The private-program
 * door matters most: `/p/{code}` is a credential in a URL, and a crawler that
 * followed one — from a referrer header, a pasted link, a browser extension —
 * would put a trainee's personal plan into a search index.
 */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = [
            'User-agent: *',
            // The panel and the member surface. Neither is reachable without a
            // session, but a crawler should not be spending requests on them.
            'Disallow: /admin',
            'Disallow: /dashboard',
            // The coded door to a private program. The code IS the credential.
            'Disallow: /p/',
            /*
             * Livewire 4 derives its endpoint prefix from APP_KEY, so the live
             * path is `/livewire-<8 hex>/update`. A `Disallow` is a prefix
             * match, so this one covers both that and a plain `/livewire/`
             * without the key having to be known here — the same reason the
             * service worker matches the prefix by shape (CLAUDE.md §3).
             */
            'Disallow: /livewire',
            // Print sheets and PDFs are the same content in another wrapper.
            'Disallow: /*/print',
            'Disallow: /*/pdf',
            '',
            'Sitemap: '.route('seo.sitemap'),
            '',
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
