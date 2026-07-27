/**
 * `<areen-rest-timer>` — the rest countdown between sets (DESIGN.md §11).
 *
 * The one rule that shapes everything here: **the clock is a deadline, not a
 * count of ticks.** Starting a 90 second rest stores `Date.now() + 90000` and
 * every frame recomputes the remainder from it. Intervals only decide when to
 * repaint; they never decide how much time has passed. That is what makes the
 * timer survive a locked screen — browsers throttle and coalesce timers in a
 * hidden document, so a `setInterval` that decrements a counter would come back
 * from a pocket reading forty seconds when eighty had gone by.
 *
 * Three layers make sure the finish actually lands:
 *
 *  1. A single `setTimeout` armed for the exact remaining milliseconds. In a
 *     background tab this is throttled but still fires, so the buzz and the tone
 *     arrive with the screen dark — which is the whole point.
 *  2. `visibilitychange`: the moment the phone is unlocked we recompute against
 *     the deadline, and if it has passed we fire immediately. A frozen page
 *     therefore reports "rest is over" on unlock rather than resuming a stale
 *     countdown.
 *  3. A wake lock held for the duration of the rest, so on a supported device
 *     the screen never sleeps in the first place and (1) happens on time.
 *
 * `sessionStorage` carries the deadline across a reload or a tab restore, so
 * even a page the OS evicted comes back with the right number on it.
 *
 * No modal, per DESIGN.md — the trainee has one hand free.
 */

import { buzz, chime, primeAudio } from './feedback.js';
import { clearSession, readSession, writeSession } from './platform.js';
import { dropWakeLock, holdWakeLock } from './wake-lock.js';

const STORAGE_KEY = 'areen:rest-timer';
const PAINT_INTERVAL = 200;
const FLASH_MS = 900;

// Ring geometry — must match the circle drawn in the Blade component.
const RING_RADIUS = 44;
const RING_LENGTH = 2 * Math.PI * RING_RADIUS;

function clockText(milliseconds) {
    const total = Math.max(0, Math.ceil(milliseconds / 1000));
    const minutes = Math.floor(total / 60);
    const seconds = total % 60;

    // Western digits everywhere (DESIGN.md §3), so no locale formatting here.
    return `${minutes}:${String(seconds).padStart(2, '0')}`;
}

class RestTimer extends HTMLElement {
    connectedCallback() {
        this.total = Math.max(5, Number.parseInt(this.dataset.seconds || '90', 10) || 90) * 1000;
        this.extendBy = Math.max(5, Number.parseInt(this.dataset.extend || '15', 10) || 15) * 1000;
        this.storageKey = `${STORAGE_KEY}:${location.pathname}:${this.id || '0'}`;

        this.display = this.querySelector('[data-display]');
        this.ring = this.querySelector('[data-ring]');
        this.doneNote = this.querySelector('[data-done]');
        this.toggleButton = this.querySelector('[data-action="toggle"]');

        this.endsAt = null;
        this.pausedRemaining = null;
        this.finished = false;
        this.paintHandle = null;
        this.finishHandle = null;
        this.flashHandle = null;

        if (this.ring) {
            this.ring.style.strokeDasharray = String(RING_LENGTH);
        }

        this.toggleButton?.addEventListener('click', () => this.toggle());
        this.querySelector('[data-action="extend"]')?.addEventListener('click', () => this.extend());
        this.querySelector('[data-action="reset"]')?.addEventListener('click', () => this.reset());

        this.onVisibility = () => {
            // Unlocking the phone lands here. Reconcile against the deadline
            // before anything else gets a chance to repaint a stale number.
            if (document.visibilityState === 'visible') this.sync();
        };

        this.onPageShow = () => this.sync();

        /*
         * Inbound seam: any view can start the rest without reaching into this
         * element. `window.dispatchEvent(new CustomEvent('areen:rest-start',
         * { detail: { seconds: 90 } }))` is the whole contract.
         */
        this.onExternalStart = (event) => {
            const seconds = Number.parseInt(event.detail?.seconds, 10);

            this.start(Number.isFinite(seconds) && seconds > 0 ? seconds * 1000 : this.total);
        };

        document.addEventListener('visibilitychange', this.onVisibility);
        window.addEventListener('pageshow', this.onPageShow);
        window.addEventListener('areen:rest-start', this.onExternalStart);

        this.restore();
        this.paint();
    }

    disconnectedCallback() {
        document.removeEventListener('visibilitychange', this.onVisibility);
        window.removeEventListener('pageshow', this.onPageShow);
        window.removeEventListener('areen:rest-start', this.onExternalStart);

        this.stopClocks();
        dropWakeLock(this);
    }

    /* ---------------------------------------------------------------- state */

    get running() {
        return this.endsAt !== null;
    }

    remaining() {
        if (this.running) return Math.max(0, this.endsAt - Date.now());
        if (this.pausedRemaining !== null) return this.pausedRemaining;

        return this.finished ? 0 : this.total;
    }

    /* --------------------------------------------------------------- clocks */

    stopClocks() {
        if (this.paintHandle) clearInterval(this.paintHandle);
        if (this.finishHandle) clearTimeout(this.finishHandle);

        this.paintHandle = null;
        this.finishHandle = null;
    }

    startClocks() {
        this.stopClocks();

        // Repaint often enough that the ring moves smoothly. Each repaint reads
        // the deadline, so a throttled interval costs accuracy of the *drawing*
        // and nothing else.
        this.paintHandle = setInterval(() => this.sync(), PAINT_INTERVAL);

        // One timeout, armed for the exact deadline. Not a chain of them.
        this.finishHandle = setTimeout(() => this.sync(), this.remaining() + 30);
    }

    sync() {
        if (this.running && this.remaining() <= 0) {
            this.finish();

            return;
        }

        this.paint();
    }

    /* -------------------------------------------------------------- actions */

    toggle() {
        if (this.running) return this.pause();

        this.start();
    }

    start(durationMs = null) {
        if (durationMs !== null) {
            this.total = durationMs;
            this.pausedRemaining = null;
        }

        const remaining = this.pausedRemaining ?? this.total;

        this.finished = false;
        this.pausedRemaining = null;
        this.endsAt = Date.now() + remaining;

        // We are inside the tap that started the rest — the only moment a mobile
        // browser will let us build a usable audio context.
        primeAudio();

        // Hold the screen for the duration. Dropped again on finish, pause or
        // reset; the manager reference-counts, so the explicit toggle keeps
        // working independently.
        holdWakeLock(this);

        this.persist();
        this.startClocks();
        this.paint();

        this.emit('areen:rest-started', { seconds: Math.round(remaining / 1000) });
    }

    pause() {
        if (! this.running) return;

        this.pausedRemaining = this.remaining();
        this.endsAt = null;

        this.stopClocks();
        dropWakeLock(this);
        this.persist();
        this.paint();
    }

    extend() {
        if (this.running) {
            this.endsAt += this.extendBy;
            this.total += this.extendBy;
            this.persist();
            this.startClocks();
        } else if (this.pausedRemaining !== null) {
            this.pausedRemaining += this.extendBy;
            this.total += this.extendBy;
            this.persist();
        } else {
            this.total += this.extendBy;
        }

        this.finished = false;
        this.paint();
    }

    reset() {
        const wasRunning = this.running;

        this.endsAt = null;
        this.pausedRemaining = null;
        this.finished = false;

        this.stopClocks();
        dropWakeLock(this);
        clearSession(this.storageKey);
        this.paint();

        if (wasRunning) this.emit('areen:rest-cancelled', {});
    }

    finish() {
        if (this.finished) return;

        this.endsAt = null;
        this.pausedRemaining = null;
        this.finished = true;

        this.stopClocks();
        dropWakeLock(this);
        clearSession(this.storageKey);

        buzz();
        chime();
        this.flash();
        this.paint();

        /*
         * Seam for phase 4: the set-logging UI can listen for this to move focus
         * into the next set's inputs. Nothing is wired to it here.
         */
        this.emit('areen:rest-finished', {});
    }

    emit(name, detail) {
        this.dispatchEvent(new CustomEvent(name, { detail, bubbles: true }));
    }

    /* ---------------------------------------------------------- persistence */

    persist() {
        writeSession(
            this.storageKey,
            JSON.stringify({
                endsAt: this.endsAt,
                pausedRemaining: this.pausedRemaining,
                total: this.total,
            }),
        );
    }

    restore() {
        const raw = readSession(this.storageKey);
        if (! raw) return;

        let saved = null;

        try {
            saved = JSON.parse(raw);
        } catch {
            clearSession(this.storageKey);

            return;
        }

        if (Number.isFinite(saved?.total)) this.total = saved.total;

        if (Number.isFinite(saved?.pausedRemaining)) {
            this.pausedRemaining = saved.pausedRemaining;

            return;
        }

        if (! Number.isFinite(saved?.endsAt)) return;

        if (saved.endsAt <= Date.now()) {
            // The rest ran out while the page was gone. Say so rather than
            // pretending the countdown never happened.
            this.finished = true;
            clearSession(this.storageKey);

            return;
        }

        this.endsAt = saved.endsAt;
        holdWakeLock(this);
        this.startClocks();
    }

    /* ------------------------------------------------------------- painting */

    flash() {
        // One flash of the bar, never a modal. `prefers-reduced-motion` collapses
        // the animation globally in app.css, so the state still toggles and the
        // movement does not.
        this.dataset.flash = 'true';

        if (this.flashHandle) clearTimeout(this.flashHandle);

        this.flashHandle = setTimeout(() => {
            delete this.dataset.flash;
        }, FLASH_MS);
    }

    paint() {
        const remaining = this.remaining();

        if (this.display) this.display.textContent = clockText(remaining);

        if (this.ring) {
            const span = this.total > 0 ? Math.min(1, Math.max(0, remaining / this.total)) : 0;

            // Full ring at the start, empty at zero.
            this.ring.style.strokeDashoffset = String(RING_LENGTH * (1 - span));
        }

        const state = this.running ? 'running' : this.finished ? 'finished' : this.pausedRemaining !== null ? 'paused' : 'idle';

        this.dataset.state = state;

        if (this.doneNote) this.doneNote.hidden = state !== 'finished';

        this.querySelectorAll('[data-label]').forEach((node) => {
            node.hidden = node.dataset.label !== (state === 'running' ? 'pause' : state === 'paused' ? 'resume' : 'start');
        });

        if (this.toggleButton) {
            const visible = this.toggleButton.querySelector('[data-label]:not([hidden])');

            if (visible) this.toggleButton.setAttribute('aria-label', visible.textContent.trim());
        }
    }
}

export function defineRestTimer() {
    if (! ('customElements' in window)) return;
    if (customElements.get('areen-rest-timer')) return;

    customElements.define('areen-rest-timer', RestTimer);
}
