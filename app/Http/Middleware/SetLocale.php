<?php

declare(strict_types=1);

namespace App\Http\Middleware;

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
}
