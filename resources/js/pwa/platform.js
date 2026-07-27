/**
 * Platform probes and a storage helper that cannot throw.
 *
 * `localStorage` is not always there: Safari in private browsing used to throw
 * on write, and a locked-down browser can make the whole property throw on
 * *access*. Every read and write goes through here so one hostile setting never
 * takes a banner — or the page — down with it.
 */

export function readFlag(key) {
    try {
        return window.localStorage?.getItem(key) ?? null;
    } catch {
        return null;
    }
}

export function writeFlag(key, value) {
    try {
        window.localStorage?.setItem(key, value);
    } catch {
        // Nothing to do: the preference simply will not be remembered.
    }
}

export function readSession(key) {
    try {
        return window.sessionStorage?.getItem(key) ?? null;
    } catch {
        return null;
    }
}

export function writeSession(key, value) {
    try {
        window.sessionStorage?.setItem(key, value);
    } catch {
        // Same deal — the timer keeps running, it just will not survive a reload.
    }
}

export function clearSession(key) {
    try {
        window.sessionStorage?.removeItem(key);
    } catch {
        // Ignored on purpose.
    }
}

/**
 * iOS detection, for the one thing it is actually needed for: Safari on iOS
 * never fires `beforeinstallprompt`, so the only way to install is the share
 * sheet, and the only way the trainee learns that is if we say so.
 *
 * Two clauses:
 *  - the plain iPhone / iPod / iPad user agent;
 *  - iPadOS 13+, which reports itself as `MacIntel` desktop Safari and gives
 *    itself away only through a touch screen. A real Mac reports
 *    `maxTouchPoints === 0`, including Macs with a Touch Bar.
 *
 * Every iOS browser is WebKit underneath and installs the same way, so the
 * check deliberately does not narrow to Safari by name.
 */
export function isIOS() {
    const ua = navigator.userAgent || '';

    if (/iPad|iPhone|iPod/.test(ua)) return true;

    return navigator.platform === 'MacIntel' && (navigator.maxTouchPoints || 0) > 1;
}

/** True when the visitor has asked the OS or the browser to move less. */
export function prefersReducedMotion() {
    if (typeof window.matchMedia !== 'function') return false;

    try {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    } catch {
        return false;
    }
}

/**
 * True when the connection is metered, saving data, or so slow that pulling a
 * whole program down in the background would be a hostile thing to do.
 * The Network Information API is Chromium-only; everywhere else we assume the
 * connection is fine, which is the same assumption a plain page load makes.
 */
export function connectionIsExpensive() {
    const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    if (! connection) return false;

    if (connection.saveData === true) return true;

    return ['slow-2g', '2g'].includes(connection.effectiveType);
}
