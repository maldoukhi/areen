/**
 * Display-mode signal.
 *
 * The bottom nav and the footer already switch themselves on
 * `@media (display-mode: standalone)` in CSS — that stays their business. This
 * module answers the questions CSS cannot: "is this the installed app?" asked
 * from JavaScript, and it mirrors the answer onto `<body>` so a rule that needs
 * a *parent* signal (rather than a media query) has one to hang off.
 *
 * Two sources, because neither is enough alone:
 *  - `matchMedia('(display-mode: standalone)')` — the standard, and what CSS sees.
 *  - `navigator.standalone` — iOS home-screen apps. Safari only started matching
 *    the media query in 16.4, and the flag is still the honest answer on the
 *    iPhones that are actually in the gym.
 */

const STANDALONE_QUERY = '(display-mode: standalone)';

// Anything that is not a browser tab. Used to decide whether the app is already
// installed; the body signal below stays strictly `standalone` so it lines up
// with the media query the rest of the stylesheet uses.
const INSTALLED_MODES = ['standalone', 'fullscreen', 'minimal-ui', 'window-controls-overlay'];

function matches(query) {
    if (typeof window.matchMedia !== 'function') return false;

    try {
        return window.matchMedia(query).matches;
    } catch {
        // An engine that cannot parse the query simply has no opinion.
        return false;
    }
}

/** True while the page runs as the installed app rather than in a browser tab. */
export function isStandalone() {
    return navigator.standalone === true || matches(STANDALONE_QUERY);
}

/**
 * True when the app is installed *in any* app-like display mode. Slightly wider
 * than `isStandalone()` on purpose: an install banner shown inside a launched
 * app is noise no matter which mode the manifest asked for.
 */
export function isInstalled() {
    if (navigator.standalone === true) return true;

    return INSTALLED_MODES.some((mode) => matches(`(display-mode: ${mode})`));
}

function paint() {
    const body = document.body;
    if (! body) return;

    const standalone = isStandalone();

    body.dataset.displayMode = standalone ? 'standalone' : 'browser';
    body.classList.toggle('is-standalone', standalone);
}

export function markDisplayMode() {
    if (document.body) {
        paint();
    } else {
        document.addEventListener('DOMContentLoaded', paint, { once: true });
    }

    // Livewire's navigation swaps the body out from under us, so the signal is
    // repainted rather than assumed to survive.
    document.addEventListener('livewire:navigated', paint);

    if (typeof window.matchMedia !== 'function') return;

    try {
        const query = window.matchMedia(STANDALONE_QUERY);

        // Safari only grew `addEventListener` on MediaQueryList in 14.
        if (typeof query.addEventListener === 'function') {
            query.addEventListener('change', paint);
        } else if (typeof query.addListener === 'function') {
            query.addListener(paint);
        }
    } catch {
        // Losing the live update is survivable; the first paint already happened.
    }
}
