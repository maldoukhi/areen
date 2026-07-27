/**
 * Screen Wake Lock, shared.
 *
 * Two things want the screen awake — the explicit toggle at the top of the day
 * page, and the rest timer while it is counting — and the platform gives us one
 * sentinel. So this is a tiny reference count: the lock is held while at least
 * one owner wants it, and dropped the moment the last one lets go.
 *
 * The browser revokes the sentinel whenever the document stops being visible
 * (tab switch, app switch, screen off). That is not an error and not something
 * the caller should have to know about — we listen for it and re-acquire when
 * the page comes back, without the owner re-asking.
 *
 * Unsupported everywhere it is unsupported: every call is a no-op that resolves,
 * never a throw. Safari only shipped this in 16.4, so a good share of the gym is
 * running without it.
 */

const owners = new Set();

let sentinel = null;
let listening = false;

export const WAKE_LOCK_CHANGED = 'areen:wake-lock-change';

export function wakeLockSupported() {
    return typeof navigator !== 'undefined'
        && 'wakeLock' in navigator
        && typeof navigator.wakeLock?.request === 'function';
}

/** True while the screen is actually being held awake right now. */
export function wakeLockActive() {
    return Boolean(sentinel) && sentinel.released !== true;
}

function announce() {
    window.dispatchEvent(
        new CustomEvent(WAKE_LOCK_CHANGED, {
            detail: { active: wakeLockActive(), wanted: owners.size > 0 },
        }),
    );
}

async function acquire() {
    if (! wakeLockSupported() || owners.size === 0) return false;
    if (wakeLockActive()) return true;

    // Requesting while hidden throws NotAllowedError by spec. Wait for the
    // visibility handler instead of burning a rejection.
    if (document.visibilityState !== 'visible') return false;

    try {
        sentinel = await navigator.wakeLock.request('screen');

        sentinel.addEventListener('release', () => {
            sentinel = null;
            announce();
        });
    } catch {
        // Denied, or the document went away mid-request. Try again on the next
        // visibility change rather than making noise.
        sentinel = null;
        announce();

        return false;
    }

    announce();

    return true;
}

async function release() {
    if (! sentinel) return;

    const held = sentinel;
    sentinel = null;

    try {
        await held.release();
    } catch {
        // Already gone. Nothing left to do.
    }

    announce();
}

function listen() {
    if (listening) return;
    listening = true;

    // The lock is dropped whenever the tab is backgrounded. Take it back the
    // moment we are visible again, or the screen sleeps mid-set on return.
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            acquire();
        } else {
            announce();
        }
    });

    // Navigating away — including a `wire:navigate` swap or a bfcache freeze —
    // must not leave somebody else's screen pinned on.
    window.addEventListener('pagehide', () => {
        release();
    });

    document.addEventListener('livewire:navigating', () => {
        owners.clear();
        release();
    });
}

/** Ask for the screen to stay awake on behalf of `owner`. */
export function holdWakeLock(owner) {
    if (! wakeLockSupported()) return Promise.resolve(false);

    listen();
    owners.add(owner);

    return acquire();
}

/** Let go on behalf of `owner`; the lock survives while anyone else still wants it. */
export function dropWakeLock(owner) {
    owners.delete(owner);

    if (owners.size > 0) {
        announce();

        return Promise.resolve(true);
    }

    return release();
}
