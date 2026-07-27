<?php

declare(strict_types=1);

use App\Http\Controllers\OpenGraphImageController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Discovery & sharing (P7)
|--------------------------------------------------------------------------
|
| The three files nobody navigates to and everything depends on: what a
| crawler may read, what there is to read, and what a pasted link looks like.
|
| They sit in their own file rather than in `routes/web.php` because they are
| not part of the site a visitor walks through, and because `robots.txt` used
| to be a static file in `public/` — which the web server would have served
| ahead of any route, silently, with a hostname baked into it. Keeping the
| three together makes it obvious that the static copy has to stay deleted.
|
| They run in the `web` group so `SetLocale` applies: the sitemap and the
| card are both rendered in the reader's language.
*/

Route::get('/robots.txt', RobotsController::class)->name('seo.robots');

Route::get('/sitemap.xml', SitemapController::class)->name('seo.sitemap');

Route::get('/og-image.png', OpenGraphImageController::class)->name('seo.og-image');
