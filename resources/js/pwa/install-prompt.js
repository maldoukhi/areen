/**
 * The one place that owns `beforeinstallprompt`.
 *
 * The event is only useful if it was cancelled at the instant it fired, and it
 * can only be answered once — so exactly one listener may hold it, and anything
 * that wants to offer installation asks here rather than listening for itself.
 * The banner and the header button are both callers.
 *
 * iOS never fires the event and has no API to install at all: the only route is
 * Share → Add to Home Screen. `canPrompt()` is therefore false there, and a
 * caller is expected to explain rather than offer a button that cannot work.
 */

const AVAILABLE = 'areen:install-available';
const INSTALLED = 'areen:install-completed';

let deferred = null;

if (typeof window !== 'undefined') {
    window.addEventListener('beforeinstallprompt', (event) => {
        // Swallow the browser's own infobar so the ask arrives in the club's voice.
        event.preventDefault();
        deferred = event;

        window.dispatchEvent(new CustomEvent(AVAILABLE));
    });

    window.addEventListener('appinstalled', () => {
        deferred = null;

        window.dispatchEvent(new CustomEvent(INSTALLED));
    });
}

export function canPrompt() {
    return deferred !== null;
}

/**
 * Show the browser's install dialog.
 *
 * Resolves to the visitor's choice, or null when there was nothing to show. The
 * event is spent either way — a declined prompt cannot be re-opened, which is
 * the browser's decision, not ours.
 */
export async function promptInstall() {
    if (! deferred) return null;

    const event = deferred;
    deferred = null;

    try {
        await event.prompt();

        const { outcome } = await event.userChoice;

        return outcome;
    } catch {
        return null;
    }
}

export function onInstallStateChange(handler) {
    window.addEventListener(AVAILABLE, handler);
    window.addEventListener(INSTALLED, handler);

    return () => {
        window.removeEventListener(AVAILABLE, handler);
        window.removeEventListener(INSTALLED, handler);
    };
}
