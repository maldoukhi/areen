/**
 * Take one program offline — and only that program.
 *
 * When a trainee opens a program we quietly pull down its day pages and the
 * exercise media on them, so the whole plan is readable in a basement with no
 * signal. The scope is deliberately narrow: this program's pages and this
 * program's pictures. Precaching the site would burn a phone plan to cache
 * pages nobody asked for.
 *
 * Which URLs? They are read off the page rather than handed down from the
 * server, so the program views stay free to change their markup: any link that
 * lives under the current program's own path and ends in `/day/<n>` is one of
 * its days. Each day page is then parsed for its images and those are fetched
 * too. A view that would rather be explicit can render
 * `<x-pwa.offline-scope :pages="…" :media="…"/>` and its list wins.
 *
 * Why write to the Workbox caches directly instead of asking the worker to
 * fetch: a `fetch()` from a page carries `mode: 'cors'` and an empty
 * `destination`, so it matches neither the navigation rule nor the image rule in
 * `vite.config.js` and would sail past the worker uncached. Writing into the
 * same cache names is safe with `ExpirationPlugin`, because on the way *out* the
 * plugin judges freshness from the response's own `Date` header, not from its
 * IndexedDB bookkeeping — an entry we put there is served like any other, and
 * gets adopted into the plugin's records the first time it is used.
 */

import { connectionIsExpensive } from './platform.js';

const PAGE_CACHE = 'areen-pages';
const MEDIA_CACHE = 'areen-exercise-media';
const THUMB_CACHE = 'areen-youtube-thumbs';

const MAX_PAGES = 16;
const MAX_MEDIA = 80;

// A cached day page older than this is worth pulling again; the coach may have
// rewritten it. Anything newer is left alone so revisiting a program is free.
const PAGE_MAX_AGE_MS = 24 * 60 * 60 * 1000;

// A program's public path, its private access-code path, and either one's days.
const PROGRAM_PATH = /^(\/(?:programs|p)\/[^/]+)(?:\/day\/\d+)?\/?$/;

let lastScope = null;

/** The base path of the program the visitor is looking at, or null. */
function programBase(pathname) {
    const match = PROGRAM_PATH.exec(pathname);

    return match ? match[1] : null;
}

function declaredScope() {
    const node = document.querySelector('[data-areen-offline-scope]');
    if (! node) return null;

    try {
        const payload = JSON.parse(node.textContent || '{}');

        return {
            pages: Array.isArray(payload.pages) ? payload.pages : [],
            media: Array.isArray(payload.media) ? payload.media : [],
        };
    } catch {
        return null;
    }
}

function absolute(value, base = location.href) {
    try {
        const url = new URL(value, base);

        return url.protocol === 'http:' || url.protocol === 'https:' ? url : null;
    } catch {
        return null;
    }
}

/** Day links belonging to this program, plus the program page itself. */
function pagesFrom(root, base) {
    const found = new Set([new URL(base, location.origin).href]);

    root.querySelectorAll('a[href]').forEach((anchor) => {
        const url = absolute(anchor.getAttribute('href'));
        if (! url || url.origin !== location.origin) return;

        // `/programs/x/day/2` yes; `/programs/xylophone/day/2` no.
        if (! url.pathname.startsWith(`${base}/day/`)) return;
        if (! /\/day\/\d+\/?$/.test(url.pathname)) return;

        url.hash = '';
        found.add(url.href);
    });

    return [...found].slice(0, MAX_PAGES);
}

/** Every picture worth having offline: exercise stills, GIFs, video posters, YouTube thumbs. */
function mediaFrom(root, base) {
    const found = new Set();

    const add = (value) => {
        if (! value) return;

        const url = absolute(value, base);
        if (! url) return;

        // Same origin, or the one third party we already cache in the worker.
        if (url.origin !== location.origin && ! url.hostname.endsWith('ytimg.com')) return;

        found.add(url.href);
    };

    root.querySelectorAll('img[src]').forEach((node) => add(node.getAttribute('src')));
    root.querySelectorAll('source[src]').forEach((node) => add(node.getAttribute('src')));
    root.querySelectorAll('video[poster]').forEach((node) => add(node.getAttribute('poster')));

    // Lazy thumbnails: the YouTube still is usually parked in a data attribute
    // until the visitor taps play (DESIGN.md §6 — the iframe never preloads).
    root.querySelectorAll('[data-thumb], [data-poster]').forEach((node) => {
        add(node.dataset.thumb);
        add(node.dataset.poster);
    });

    return [...found];
}

async function isFresh(cache, url) {
    const hit = await cache.match(url);
    if (! hit) return false;

    const date = hit.headers.get('date');
    if (! date) return true;

    const stamp = Date.parse(date);

    return Number.isNaN(stamp) ? true : Date.now() - stamp < PAGE_MAX_AGE_MS;
}

async function warmPage(cache, url) {
    if (await isFresh(cache, url)) {
        const hit = await cache.match(url);

        return hit ? hit.clone().text() : null;
    }

    // Ask like a navigation would, so Laravel renders the page and not a JSON
    // shape, and so the session cookie travels with it for private programs.
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: { Accept: 'text/html,application/xhtml+xml' },
    });

    if (! response.ok) return null;
    if (! (response.headers.get('content-type') || '').includes('text/html')) return null;

    const body = await response.clone().text();

    // Keyed by the bare URL, which is what a later navigation will look up.
    await cache.put(url, response);

    return body;
}

async function warmMedia(url) {
    const crossOrigin = new URL(url).origin !== location.origin;
    const cache = await caches.open(crossOrigin ? THUMB_CACHE : MEDIA_CACHE);

    if (await cache.match(url)) return false;

    // Third-party thumbnails answer opaquely; `cache.put` accepts that, and the
    // worker's own rule for ytimg already stores status-0 responses.
    const response = await fetch(url, crossOrigin ? { mode: 'no-cors' } : { credentials: 'same-origin' });

    if (response.type !== 'opaque' && ! response.ok) return false;

    await cache.put(url, response);

    return true;
}

async function warm(base) {
    const pageCache = await caches.open(PAGE_CACHE);
    const declared = declaredScope();

    const pages = declared?.pages?.length
        ? declared.pages.map((url) => absolute(url)).filter(Boolean).map((url) => url.href).slice(0, MAX_PAGES)
        : pagesFrom(document, base);

    const media = new Set(
        declared?.media?.length
            ? declared.media.map((url) => absolute(url)).filter(Boolean).map((url) => url.href)
            : mediaFrom(document, base),
    );

    let cachedPages = 0;

    // One at a time. A phone on one bar should not be asked for sixteen parallel
    // page loads while the trainee is still reading the first one.
    for (const url of pages) {
        try {
            const html = await warmPage(pageCache, url);
            cachedPages += 1;

            if (html && ! declared?.media?.length) {
                const parsed = new DOMParser().parseFromString(html, 'text/html');

                mediaFrom(parsed, url).forEach((item) => media.add(item));
            }
        } catch {
            // Offline halfway through, or one bad page. Keep what we have.
        }
    }

    let cachedMedia = 0;

    for (const url of [...media].slice(0, MAX_MEDIA)) {
        try {
            if (await warmMedia(url)) cachedMedia += 1;
        } catch {
            // A missing picture is not a reason to abandon the rest of the plan.
        }
    }

    window.dispatchEvent(
        new CustomEvent('areen:offline-scope-ready', {
            detail: { base, pages: cachedPages, media: cachedMedia },
        }),
    );
}

function idle(callback) {
    if (typeof window.requestIdleCallback === 'function') {
        window.requestIdleCallback(callback, { timeout: 4000 });
    } else {
        setTimeout(callback, 1500);
    }
}

async function maybeWarm() {
    if (! ('caches' in window) || ! ('serviceWorker' in navigator)) return;
    if (! navigator.onLine) return;
    if (connectionIsExpensive()) return;

    const base = programBase(location.pathname);
    if (! base) return;

    // Once per program per page life; `livewire:navigated` fires on first load too.
    if (lastScope === base) return;
    lastScope = base;

    // Never be the reason a phone runs out of room.
    try {
        const estimate = await navigator.storage?.estimate?.();

        if (estimate?.quota && estimate.usage / estimate.quota > 0.9) return;
    } catch {
        // No estimate available; carry on.
    }

    try {
        await warm(base);
    } catch {
        // Nothing here is worth breaking a page over.
    }
}

export function watchProgramScope() {
    // After the page is usable, not before — this is background work.
    if (document.readyState === 'complete') {
        idle(maybeWarm);
    } else {
        window.addEventListener('load', () => idle(maybeWarm), { once: true });
    }

    document.addEventListener('livewire:navigated', () => idle(maybeWarm));
}
