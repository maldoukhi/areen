import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { VitePWA } from 'vite-plugin-pwa';
import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';

/*
 * `/offline` is rendered by Laravel, so Workbox cannot hash it off disk. We hash
 * its Blade sources instead: edit the page or the layout it sits in, and the
 * precached copy is replaced on the next visit.
 */
const offlineRevision = createHash('sha256')
    .update(readFileSync('resources/views/offline.blade.php'))
    .update(readFileSync('resources/views/layouts/app.blade.php'))
    .digest('hex')
    .slice(0, 16);

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        VitePWA({
            // The update bar is ours to show — never swap the worker out silently.
            registerType: 'prompt',
            injectRegister: false,
            strategies: 'generateSW',
            filename: 'sw.js',
            devOptions: { enabled: false },
            /*
             * The manifest is served by App\Http\Controllers\ManifestController so it
             * can follow the reader's locale. Generating a second static copy here
             * would give us two sources of truth that quietly drift apart.
             */
            manifest: false,
            workbox: {
                // One self-contained worker file, so lifting it to `public/` is safe.
                inlineWorkboxRuntime: true,
                globDirectory: 'public/build',
                globPatterns: ['**/*.{js,css,woff2}'],
                /*
                 * Workbox writes precache URLs relative to the worker. The worker ends up
                 * at `/sw.js` while the assets stay under `/build/`, so without this every
                 * precached URL would resolve one directory too high and 404.
                 */
                modifyURLPrefix: { '': '/build/' },
                // Served by Laravel, so it has to be named explicitly.
                additionalManifestEntries: [{ url: '/offline', revision: offlineRevision }],
                // Laravel renders HTML per request; there is no app shell to fall back to.
                navigateFallback: null,
                cleanupOutdatedCaches: true,
                clientsClaim: false,
                skipWaiting: false,
                navigationPreload: true,
                runtimeCaching: [
                    {
                        /**
                         * Livewire round-trips and every other write must never be cached.
                         * A stale Livewire response breaks the app silently, so this rule
                         * comes first and matches before any other rule can.
                         *
                         * Livewire 4 derives its endpoint prefix from APP_KEY — the live
                         * path is `/livewire-<8 hex>/update`, not `/livewire/update`. The
                         * key differs between this build machine and the server, so the
                         * prefix is matched by shape rather than baked in as a literal.
                         */
                        urlPattern: ({ url, request }) =>
                            request.method !== 'GET' || /^\/livewire[-/]/.test(url.pathname),
                        handler: 'NetworkOnly',
                    },
                    {
                        // Self-hosted fonts change only when we replace the files.
                        urlPattern: ({ url }) => url.pathname.startsWith('/fonts/'),
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'areen-fonts',
                            expiration: { maxEntries: 16, maxAgeSeconds: 60 * 60 * 24 * 365 },
                            cacheableResponse: { statuses: [0, 200] },
                        },
                    },
                    {
                        urlPattern: ({ url }) => url.pathname.startsWith('/brand/'),
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'areen-brand',
                            expiration: { maxEntries: 24 },
                        },
                    },
                    {
                        // Exercise stills and GIFs. Capped so a big library cannot fill the disk.
                        urlPattern: ({ request, sameOrigin }) => sameOrigin && request.destination === 'image',
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'areen-exercise-media',
                            expiration: { maxEntries: 200, maxAgeSeconds: 60 * 60 * 24 * 30 },
                            cacheableResponse: { statuses: [0, 200] },
                        },
                    },
                    {
                        // YouTube stills only. The iframe is never loaded until tapped.
                        urlPattern: ({ url }) => url.hostname.endsWith('ytimg.com'),
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'areen-youtube-thumbs',
                            expiration: { maxEntries: 120, maxAgeSeconds: 60 * 60 * 24 * 30 },
                            cacheableResponse: { statuses: [0, 200] },
                        },
                    },
                    {
                        // Pages: fresh when the network answers, cached copy when it does not.
                        urlPattern: ({ request }) => request.mode === 'navigate',
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'areen-pages',
                            networkTimeoutSeconds: 4,
                            expiration: { maxEntries: 60, maxAgeSeconds: 60 * 60 * 24 * 14 },
                            cacheableResponse: { statuses: [200] },
                            // A page we have never opened lands on our own offline page,
                            // never on the browser's blank error screen.
                            precacheFallback: { fallbackURL: '/offline' },
                        },
                    },
                ],
            },
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
