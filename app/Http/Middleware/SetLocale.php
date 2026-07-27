<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Seo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the request locale from the session, falling back to the signed-in
 * user's stored preference and then to the application default.
 *
 * The locale lives in the session rather than in a URL prefix, so a program
 * link shared in a WhatsApp group opens in whichever language the reader
 * already chose.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->resolve($request));

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        $supported = config('areen.locales');

        $candidates = [
            $this->pinnedByUrl($request),
            $request->session()->get('locale'),
            $request->user()?->locale,
            config('app.locale'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && array_key_exists($candidate, $supported)) {
                return $candidate;
            }
        }

        return (string) config('app.fallback_locale');
    }

    /**
     * A language pinned in the address itself, via `?lang=en`.
     *
     * This exists for `hreflang`. A session-held locale gives every page exactly
     * one URL, and an `hreflang` set whose entries all point at the same address
     * is invalid — a crawler has no session to carry a preference in, so without
     * this the alternates would be a lie. `App\Support\Seo` builds them from
     * this parameter.
     *
     * The choice is written to the session as well, so a reader who arrives on
     * an English result from a search engine stays in English when they follow
     * the next link. It is the same decision the header's switcher makes, just
     * reached through a link rather than a form.
     */
    private function pinnedByUrl(Request $request): ?string
    {
        $pinned = $request->query(Seo::LOCALE_QUERY);

        if (! is_string($pinned) || ! array_key_exists($pinned, (array) config('areen.locales'))) {
            return null;
        }

        if ($request->hasSession() && $request->session()->get('locale') !== $pinned) {
            $request->session()->put('locale', $pinned);
        }

        return $pinned;
    }
}
