/**
 * `<areen-wake-lock>` — the "keep the screen awake" switch (DESIGN.md §11).
 *
 * A phone that sleeps between sets means wet hands on a lock screen. The switch
 * is a plain `role="switch"` button, its state comes from the shared wake-lock
 * manager rather than from what the button thinks it did, and the choice is
 * remembered per device so it is a once-ever tap and not a warm-up ritual.
 *
 * Where the API does not exist the switch never appears: the server renders it
 * hidden and only a supported browser reveals it. A dead toggle is worse than
 * no toggle.
 */

import { readFlag, writeFlag } from './platform.js';
import { WAKE_LOCK_CHANGED, dropWakeLock, holdWakeLock, wakeLockSupported } from './wake-lock.js';

const PREFERENCE_KEY = 'areen:wake-lock';
const OWNER = 'toggle';

class WakeLockToggle extends HTMLElement {
    connectedCallback() {
        if (! wakeLockSupported()) return;

        this.button = this.querySelector('[data-action="toggle"]');
        if (! this.button) return;

        this.hidden = false;

        this.button.addEventListener('click', () => this.toggle());

        this.onChange = () => this.paint();
        window.addEventListener(WAKE_LOCK_CHANGED, this.onChange);

        // Restore the trainee's standing answer. The manager itself handles a
        // hidden document by deferring until the page is visible.
        this.wanted = readFlag(PREFERENCE_KEY) === 'on';

        if (this.wanted) holdWakeLock(OWNER);

        this.paint();
    }

    disconnectedCallback() {
        window.removeEventListener(WAKE_LOCK_CHANGED, this.onChange);
        dropWakeLock(OWNER);
    }

    toggle() {
        this.wanted = ! this.wanted;

        writeFlag(PREFERENCE_KEY, this.wanted ? 'on' : 'off');

        if (this.wanted) {
            holdWakeLock(OWNER);
        } else {
            dropWakeLock(OWNER);
        }

        this.paint();
    }

    paint() {
        if (! this.button) return;

        // `wanted` — not "is the lock live right now". The sentinel is dropped
        // every time the phone is backgrounded and taken again on return; the
        // switch would otherwise flicker off behind the trainee's back.
        this.button.setAttribute('aria-checked', this.wanted ? 'true' : 'false');
        this.dataset.state = this.wanted ? 'on' : 'off';
    }
}

export function defineWakeLockToggle() {
    if (! ('customElements' in window)) return;
    if (customElements.get('areen-wake-lock')) return;

    customElements.define('areen-wake-lock', WakeLockToggle);
}
