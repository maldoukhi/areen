<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Serves the web app manifest for the locale the visitor is actually reading in,
 * so an installed icon and its splash screen come out in the right language and
 * the right text direction.
 */
class ManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $locale = app()->getLocale();

        return response()
            ->json([
                'id' => '/',
                'name' => __('common.app_name'),
                'short_name' => __('common.app_name'),
                'description' => __('common.tagline'),
                'lang' => $locale,
                'dir' => config("areen.locales.{$locale}.dir", 'rtl'),
                'display' => 'standalone',
                'orientation' => 'portrait',
                'theme_color' => config('areen.brand.theme_color'),
                'background_color' => config('areen.brand.background_color'),
                'start_url' => '/',
                'scope' => '/',
                'icons' => [
                    ['src' => '/brand/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                    ['src' => '/brand/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                    ['src' => '/brand/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
                ],
            ], options: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Content-Type', 'application/manifest+json; charset=utf-8');
    }
}
