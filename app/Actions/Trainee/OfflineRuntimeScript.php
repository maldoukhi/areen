<?php

declare(strict_types=1);

namespace App\Actions\Trainee;

use RuntimeException;

/**
 * The offline runtime, read off disk and handed to the page as one inline script.
 *
 * ## Why inline rather than a bundled asset
 *
 * The queue has to run on a page that was served from the service worker's cache
 * with no network at all. Workbox precaches `public/build/**` and caches HTML
 * navigations in `areen-pages`; a `<script src>` is neither — its request is not
 * a navigation and not an image, so no runtime-caching rule in `vite.config.js`
 * matches it and it would simply 404 the first time the trainee opens the app in
 * a basement. That is precisely the moment the queue exists to serve.
 *
 * Inlining sidesteps the problem completely: the script travels inside the HTML
 * that Workbox already caches, so if the page is available at all, so is the code
 * that makes it work. It also costs zero extra requests on the critical path of a
 * screen whose whole promise is that it opens instantly.
 *
 * The modules stay as ordinary files under `resources/js/offline/` — readable,
 * diffable, and reviewable next to `resources/js/pwa/`. They are plain scripts
 * rather than ES modules on purpose, so that concatenating them is a valid
 * program: each is an IIFE that hangs one object off `window.Areen` and returns
 * early if it has already run, which is what makes a `wire:navigate` body swap
 * harmless.
 */
class OfflineRuntimeScript
{
    /**
     * Load-bearing order: the store must exist before the sync loop reaches for
     * it, and both before the element that drives them.
     *
     * @var list<string>
     */
    private const MODULES = [
        'store.js',
        'sync.js',
        'logger.js',
    ];

    private static ?string $cached = null;

    /**
     * Read once per request. The files are a few kilobytes and never change
     * between requests within one deploy, so anything cleverer than this would be
     * a cache to invalidate for no measurable gain.
     */
    public static function contents(): string
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $sources = [];

        foreach (self::MODULES as $module) {
            $path = resource_path('js/offline/'.$module);

            if (! is_file($path)) {
                throw new RuntimeException("The offline runtime module [{$module}] is missing.");
            }

            $sources[] = trim((string) file_get_contents($path));
        }

        return self::$cached = implode("\n\n", $sources);
    }

    /**
     * Only ever called from tests that want a cold read.
     */
    public static function flush(): void
    {
        self::$cached = null;
    }
}
