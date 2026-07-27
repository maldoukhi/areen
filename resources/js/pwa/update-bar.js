/**
 * `<areen-update-bar>` — "a new version is ready", never a silent swap.
 *
 * `register-sw.js` registers the worker with `registerType: 'prompt'` and
 * dispatches `areen:update-available` once a replacement is installed and
 * waiting. This is the visible half: a bar that offers the reload and does
 * nothing until it is tapped.
 *
 * Swapping assets out from under somebody who is mid-set is how you lose their
 * set, so the decision stays with them.
 *
 * The bar answers to the registration, not to a remembered event. An earlier
 * version parked the callback in a module variable and showed itself whenever
 * that variable was set — and because `wire:navigate` swaps the body while the
 * module lives on, the element reconnected on every navigation and the bar came
 * back every single time, long after the update was gone. The only question
 * asked now is whether a worker is waiting right now.
 */

const DISMISS_KEY = 'areen.update-dismissed';

// A hint only. Whether to show is decided by looking at the registration.
let latestRegistration = null;

if (typeof window !== 'undefined') {
    window.addEventListener('areen:update-available', (event) => {
        latestRegistration = event.detail?.registration ?? null;
    });
}

async function waitingWorker() {
    if (! ('serviceWorker' in navigator)) return null;

    const registration = latestRegistration
        ?? await navigator.serviceWorker.getRegistration('/').catch(() => null);

    return registration?.waiting ?? null;
}

/**
 * A dismissal is remembered against the waiting worker's own script URL, so
 * declining this update stays declined while a genuinely newer one still gets to
 * ask. Session storage, because a new session deserves the offer again.
 */
function dismissedFor(worker) {
    try {
        return window.sessionStorage.getItem(DISMISS_KEY) === worker.scriptURL;
    } catch {
        return false;
    }
}

function rememberDismissal(worker) {
    try {
        window.sessionStorage.setItem(DISMISS_KEY, worker.scriptURL);
    } catch {
        // Private mode. Not remembering is a smaller problem than throwing.
    }
}

class UpdateBar extends HTMLElement {
    connectedCallback() {
        this.applyButton = this.querySelector('[data-action="apply"]');
        this.dismissButton = this.querySelector('[data-action="dismiss"]');
        this.idleLabel = this.querySelector('[data-label="apply"]');
        this.busyLabel = this.querySelector('[data-label="applying"]');

        this.applyButton?.addEventListener('click', () => this.apply());
        this.dismissButton?.addEventListener('click', () => this.dismiss());

        this.onAvailable = () => this.refresh();
        window.addEventListener('areen:update-available', this.onAvailable);

        this.refresh();
    }

    disconnectedCallback() {
        window.removeEventListener('areen:update-available', this.onAvailable);
    }

    /** Visible only while a worker is genuinely waiting and has not been declined. */
    async refresh() {
        const worker = await waitingWorker();

        this.hidden = ! worker || dismissedFor(worker);
    }

    async dismiss() {
        const worker = await waitingWorker();

        if (worker) rememberDismissal(worker);

        this.hidden = true;
    }

    async apply() {
        const worker = await waitingWorker();

        if (! worker) {
            this.hidden = true;

            return;
        }

        if (this.applyButton) {
            this.applyButton.disabled = true;
            this.applyButton.setAttribute('aria-busy', 'true');
        }

        if (this.idleLabel) this.idleLabel.hidden = true;
        if (this.busyLabel) this.busyLabel.hidden = false;

        worker.postMessage({ type: 'SKIP_WAITING' });

        /*
         * `register-sw.js` reloads on `controllerchange`. If that never arrives —
         * the worker died, or the browser declined the handover — reload anyway
         * rather than leaving a disabled button on screen for good.
         */
        window.setTimeout(() => window.location.reload(), 3000);
    }
}

export function defineUpdateBar() {
    if (! ('customElements' in window)) return;
    if (customElements.get('areen-update-bar')) return;

    customElements.define('areen-update-bar', UpdateBar);
}
