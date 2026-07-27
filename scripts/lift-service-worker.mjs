/**
 * Moves the generated service worker from `public/build` to `public/`.
 *
 * A worker can only control URLs at or below its own path. Left where Vite
 * writes it, `/build/sw.js` would control `/build/*` and nothing else — every
 * page on the site would go uncontrolled and the app would never work offline.
 *
 * This runs as a build step rather than a Vite plugin hook because
 * vite-plugin-pwa writes the worker after plugin `closeBundle` hooks have
 * already fired.
 *
 * The Workbox runtime is inlined (see `vite.config.js`), so the worker is a
 * single self-contained file and moving it breaks no imports.
 */
import { renameSync, existsSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const from = resolve(root, 'public/build/sw.js');
const to = resolve(root, 'public/sw.js');

if (! existsSync(from)) {
    console.error('[areen] sw.js was not generated — the app will not work offline.');
    process.exit(1);
}

renameSync(from, to);
console.log('[areen] service worker moved to public/sw.js (scope: /)');
