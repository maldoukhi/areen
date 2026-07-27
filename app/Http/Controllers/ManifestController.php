<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;

/**
 * Serves the web app manifest for the locale the visitor is actually reading in,
 * so an installed icon and its splash screen come out in the right language and
 * the right text direction.
 *
 * The installed app carries the club's name, not the platform's: someone adding
 * this to their home screen is joining a gym, not adopting a product. Areen's own
 * name is the fallback for an installation that has not been branded yet.
 */
class ManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $locale = app()->getLocale();
        $club = rescue(fn (): ?Setting => Setting::current(), null, false);

        $name = rescue(fn (): ?string => $club?->club_name, null, false)
            ?: __('common.app_name');

        return response()
            ->json([
                // A stable id keeps this the same installed app across a rename.
                'id' => '/',
                'name' => $name,
                'short_name' => self::shortName($name),
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
                    ['src' => self::asset('/brand/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                    ['src' => self::asset('/brand/icon-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
                ],
            ], options: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Content-Type', 'application/manifest+json; charset=utf-8')
            // The manifest describes what is installed; a cached copy hides a rename.
            ->header('Cache-Control', 'no-cache, must-revalidate');
    }

    /**
     * The label under a home-screen icon truncates at roughly a dozen
     * characters, so a multi-word club name is reduced to its first word — the
     * one that identifies the club. "قسورة الأزرق" becomes "قسورة".
     */
    private static function shortName(string $name): string
    {
        $words = preg_split('/\s+/u', trim($name)) ?: [$name];

        if (count($words) > 1 && mb_strlen($name) > 10) {
            return $words[0];
        }

        return mb_strimwidth($name, 0, 12, '');
    }

    /**
     * Icons are fingerprinted by their own contents.
     *
     * An installed app re-reads the manifest, but a launcher will happily keep
     * showing an icon it already has for an unchanged URL — and the service
     * worker caches `/brand/*` as well. Changing the URL when the bytes change is
     * what actually gets a new icon onto the home screen.
     */
    private static function asset(string $path): string
    {
        $file = public_path(ltrim($path, '/'));

        $version = is_file($file) ? substr((string) md5_file($file), 0, 8) : null;

        return $version === null ? $path : $path.'?v='.$version;
    }
}
