/**
 * `<areen-update-bar>` — "a new version is ready", never a silent swap.
 *
 * `register-sw.js` registers the worker with `registerType: 'prompt'` and
 * dispatches `areen:update-available` with an `apply()` callback once a new
 * worker is installed and waiting. This is the visible half: a bar that offers
 * the reload and does nothing until it is tapped.
 *
 * Swapping assets out from under somebody who is mid-set is how you lose their
 * set, so the decision stays with them.
 */

/*
 * The event can land before the element upgrades, so the last one is parked here
 * at module scope — the same trick the install banner uses for
 * `beforeinstallprompt`.
 */
let pendingApply = null;

if (typeof window !== 'undefined') {
    window.addEventListener('areen:update-available', (event) => {
        if (typeof event.detail?.apply === 'function') {
            pendingApply = event.detail.apply;
        }
    });
}

class UpdateBar extends HTMLElement {
    connectedCallback() {
        this.applyButton = this.querySelector('[data-action="apply"]');
        this.idleLabel = this.querySelector('[data-label="apply"]');
        this.busyLabel = this.querySelector('[data-label="applying"]');

        this.applyButton?.addEventListener('click', () => this.apply());

        this.onAvailable = () => this.show();
        window.addEventListener('areen:update-available', this.onAvailable);

        if (pendingApply) this.show();
    }

    disconnectedCallback() {
        window.removeEventListener('areen:update-available', this.onAvailable);
    }

    show() {
        this.hidden = false;
    }

    apply() {
        if (! pendingApply) return;

        // The page reloads on `controllerchange`; until then, make it obvious the
        // tap landed and make a second tap impossible.
        if (this.applyButton) {
            this.applyButton.disabled = true;
            this.applyButton.setAttribute('aria-busy', 'true');
        }

        if (this.idleLabel) this.idleLabel.hidden = true;
        if (this.busyLabel) this.busyLabel.hidden = false;

        const apply = pendingApply;
        pendingApply = null;

        try {
            apply();
        } catch {
            // The waiting worker went away on its own. The next load picks it up.
            this.hidden = true;
        }
    }
}

export function defineUpdateBar() {
    if (! ('customElements' in window)) return;
    if (customElements.get('areen-update-bar')) return;

    customElements.define('areen-update-bar', UpdateBar);
}
