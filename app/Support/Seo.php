<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Everything a crawler or a chat app needs to describe one page.
 *
 * A page declares what makes it different — its description, its social image,
 * whether it may be indexed — by pushing `<x-seo.page>` into the head stack.
 * The layout then renders `<x-seo.meta>` once, and that component asks this
 * class for the finished set of tags. Anything a page did not declare falls
 * back to the club's own identity from the `settings` row, so a page that says
 * nothing still shares correctly.
 *
 * The declaration is stored on the current request rather than in a static, so
 * it cannot leak from one request into the next inside a long-lived worker or
 * across two calls in the same test.
 */
final class Seo
{
    /**
     * The query parameter that pins a page to one language.
     *
     * Locale lives in the session (CLAUDE.md §3), which is right for a human
     * following a link but leaves every page with exactly one URL — and an
     * `hreflang` set whose entries all point at the same address is invalid.
     * This parameter gives each language a real, distinct, self-canonical URL
     * for crawlers, without introducing a `/{locale}/` prefix.
     */
    public const string LOCALE_QUERY = 'lang';

    private const string REQUEST_KEY = 'areen.seo.page';

    /**
     * Open Graph wants a language_TERRITORY pair, not a bare language tag.
     *
     * @var array<string, string>
     */
    private const array OPEN_GRAPH_LOCALES = [
        'ar' => 'ar_AR',
        'en' => 'en_US',
    ];

    private const int DESCRIPTION_LIMIT = 160;

    /**
     * Records what this page wants said about it. Called from
     * `<x-seo.page>`, which pages push into the head stack.
     */
    public static function declare(
        ?string $description = null,
        ?string $image = null,
        string $type = 'website',
        bool $noindex = false,
        ?string $canonical = null,
    ): void {
        request()->attributes->set(self::REQUEST_KEY, [
            'description' => self::trim($description),
            'image' => $image,
            'type' => $type,
            'noindex' => $noindex,
            'canonical' => $canonical,
        ]);
    }

    /**
     * The finished tag set. `$documentTitle` is the layout's own `<title>`, so
     * `og:title` can never drift away from what the tab says.
     *
     * @return array{
     *     title: string,
     *     description: string,
     *     canonical: string,
     *     alternates: array<string, string>,
     *     image: string,
     *     image_alt: string,
     *     type: string,
     *     noindex: bool,
     *     site_name: string,
     *     locale: string,
     *     alternate_locales: list<string>,
     * }
     */
    public static function tags(string $documentTitle): array
    {
        /** @var array<string, mixed> $page */
        $page = request()->attributes->get(self::REQUEST_KEY, []);

        $club = self::club();
        $locale = app()->getLocale();

        $siteName = self::trim($club?->club_name) ?: __('common.app_name');

        $description = self::trim($page['description'] ?? null)
            ?: self::trim($club?->description)
            ?: self::trim($club?->tagline)
            ?: __('common.tagline');

        $canonical = is_string($page['canonical'] ?? null) && $page['canonical'] !== ''
            ? $page['canonical']
            : self::localisedUrl($locale);

        return [
            'title' => $documentTitle,
            'description' => Str::limit($description, self::DESCRIPTION_LIMIT),
            'canonical' => $canonical,
            'alternates' => self::alternates(),
            'image' => is_string($page['image'] ?? null) && $page['image'] !== ''
                ? $page['image']
                : OpenGraphImage::url(),
            'image_alt' => $siteName,
            'type' => is_string($page['type'] ?? null) && $page['type'] !== '' ? $page['type'] : 'website',
            'noindex' => (bool) ($page['noindex'] ?? false),
            'site_name' => $siteName,
            'locale' => self::OPEN_GRAPH_LOCALES[$locale] ?? $locale,
            'alternate_locales' => array_values(array_diff(
                array_intersect_key(self::OPEN_GRAPH_LOCALES, (array) config('areen.locales')),
                [self::OPEN_GRAPH_LOCALES[$locale] ?? $locale],
            )),
        ];
    }

    /**
     * Every language this page exists in, plus `x-default`.
     *
     * @return array<string, string>
     */
    public static function alternates(): array
    {
        $alternates = [];

        foreach (array_keys((array) config('areen.locales', [])) as $locale) {
            $alternates[(string) $locale] = self::localisedUrl((string) $locale);
        }

        // The default language is what a reader with no preference should land
        // on, and it is the address without the parameter.
        $alternates['x-default'] = self::localisedUrl(self::defaultLocale());

        return $alternates;
    }

    /**
     * The address of the current page in one specific language.
     *
     * The query string is deliberately dropped: `/exercises?muscle=chest` is
     * the library with a filter applied, not a second page, and every filter
     * permutation canonicalising to `/exercises` is what keeps them out of an
     * index as near-duplicates.
     */
    public static function localisedUrl(string $locale): string
    {
        $base = url()->current();

        return $locale === self::defaultLocale()
            ? $base
            : $base.'?'.http_build_query([self::LOCALE_QUERY => $locale]);
    }

    /**
     * The language a URL with no `?lang=` is served in — the one `x-default`
     * and the canonical address belong to.
     *
     * Read from `app.fallback_locale`, NOT from `app.locale`, and that is not a
     * detail: `Application::setLocale()` writes the request's locale back into
     * `config('app.locale')`, so reading it here made "the default language"
     * mean "whatever this reader happens to be using". An English reader was
     * served `hreflang="ar" href="…?lang=ar"` with the bare URL claimed for
     * English — the alternates inverted themselves per visitor. The fallback is
     * the one setting nothing rewrites mid-request.
     */
    public static function defaultLocale(): string
    {
        $supported = (array) config('areen.locales', []);

        foreach ([config('app.fallback_locale'), config('app.locale')] as $candidate) {
            if (is_string($candidate) && array_key_exists($candidate, $supported)) {
                return $candidate;
            }
        }

        return (string) array_key_first($supported);
    }

    /**
     * The settings row, or null on an installation that has none yet. Reads are
     * guarded for the same reason the header's are: a fresh clone must render.
     */
    private static function club(): ?Setting
    {
        return rescue(fn (): ?Setting => Setting::current(), null, false);
    }

    private static function trim(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');
    }
}
