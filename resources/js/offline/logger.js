/**
 * `<areen-set-logger>` — the set-logging screen, running entirely on the phone.
 *
 * ## Why this is not a Livewire component
 *
 * Livewire needs a round trip to change anything. The screen this element drives
 * has to work with the radio off, so every interaction is resolved locally: the
 * tap writes to IndexedDB, the row repaints from its own data attributes, and the
 * network is attempted afterwards as a detail the trainee never waits on. The
 * server renders this markup once; from then on the page is the trainee's.
 *
 * ## The order of operations, which is the whole point
 *
 *   1. mint `client_uuid` — before anything else, so the round has an identity
 *      the moment it exists rather than one the server hands back;
 *   2. write to IndexedDB — so a tab killed one second later loses nothing;
 *   3. repaint optimistically — the set reads as logged immediately, because it
 *      is: the record exists;
 *   4. start the rest timer;
 *   5. attempt the network, and say nothing if it fails.
 *
 * A round already logged keeps its uuid, so correcting a mistyped weight and
 * tapping again updates the same row on the server instead of adding a second.
 *
 * ## What is deliberately absent
 *
 * There is no disabled state driven by connectivity. DESIGN.md §11 is explicit:
 * never refuse input because the network is down. The button behaves identically
 * on five bars and on none, and the only difference the trainee can see is a
 * small ember dot on rounds that have not left the phone yet.
 *
 * Presentation is driven by two data attributes — `data-state` and `data-sync` —
 * which Tailwind's `group-data-*` variants read directly, so this file changes
 * attributes and never class lists.
 */

(() => {
    const areen = (window.Areen = window.Areen || {});

    if (typeof window.customElements === 'undefined' || window.customElements.get('areen-set-logger')) return;

    // How long the rest panel stays up after the countdown ends, so the trainee
    // sees it finish rather than finding it already gone.
    const REST_LINGER_MS = 6000;

    function number(value) {
        const parsed = Number.parseFloat(String(value).replace(',', '.'));

        return Number.isFinite(parsed) ? parsed : null;
    }

    class SetLogger extends HTMLElement {
        connectedCallback() {
            this.performedOn = this.dataset.date || '';
            this.restPanel = null;
            this.restHandle = null;

            // Rounds this element has just queued. Until the server names them,
            // no stale sync report is allowed to clear their ember dot.
            this.optimistic = new Set();

            this.onClick = (event) => this.handleClick(event);
            this.onSyncState = (event) => this.applySyncState(event.detail || {});
            this.onRestStarted = () => this.showRest(true);
            this.onRestStopped = (event) => this.showRest(false, event.type === 'areen:rest-finished');

            // Click is delegated to the element itself, so it is safe to bind
            // before the rows exist.
            this.addEventListener('click', this.onClick);
            window.addEventListener('areen:sync-state', this.onSyncState);
            document.addEventListener('areen:rest-started', this.onRestStarted);
            document.addEventListener('areen:rest-cancelled', this.onRestStopped);
            document.addEventListener('areen:rest-finished', this.onRestStopped);

            /*
             * `connectedCallback` fires the instant the *opening* tag is parsed,
             * so on a first page load none of the rows or the rest panel exist
             * yet — the runtime is an inline classic script and therefore runs
             * before them, which is exactly what makes it available offline.
             * Anything that reads children waits for the parser to finish.
             * A `wire:navigate` swap inserts the element with its children
             * already attached, and lands in the immediate branch.
             */
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.hydrate(), { once: true });
            } else {
                this.hydrate();
            }
        }

        hydrate() {
            if (! this.isConnected) return;

            this.restPanel = this.querySelector('[data-rest-panel]');

            this.restore();
            this.countLogged();
        }

        disconnectedCallback() {
            this.removeEventListener('click', this.onClick);
            window.removeEventListener('areen:sync-state', this.onSyncState);
            document.removeEventListener('areen:rest-started', this.onRestStarted);
            document.removeEventListener('areen:rest-cancelled', this.onRestStopped);
            document.removeEventListener('areen:rest-finished', this.onRestStopped);

            if (this.restHandle) clearTimeout(this.restHandle);
        }

        rows() {
            return [...this.querySelectorAll('[data-set-row]')];
        }

        rowFor(uuid) {
            return this.rows().find((row) => row.dataset.uuid === uuid) || null;
        }

        /* --------------------------------------------------------- restoring */

        /**
         * Rounds still sitting in the queue are repainted over the server's
         * markup. This is what makes a reload with no signal look exactly like the
         * screen the trainee left: the page comes from the service worker's cache,
         * knowing nothing about the last hour, and the queue fills it back in.
         */
        async restore() {
            if (! areen.offlineStore) return;

            let pending = [];

            try {
                pending = await areen.offlineStore.all();
            } catch {
                return;
            }

            pending.forEach((record) => {
                const row = this.querySelector(
                    `[data-set-row][data-program-exercise="${record.program_exercise_id}"][data-set-number="${record.set_number}"]`,
                );

                if (! row || record.performed_on !== this.performedOn) return;

                row.dataset.uuid = record.client_uuid;
                row.dataset.state = 'logged';
                row.dataset.sync = 'pending';

                const reps = row.querySelector('[data-reps]');
                const weight = row.querySelector('[data-weight]');

                if (reps && record.reps_done !== null && record.reps_done !== undefined) reps.value = record.reps_done;
                if (weight && record.weight !== null && record.weight !== undefined) weight.value = record.weight;
            });

            this.countLogged();

            if (areen.offlineSync) areen.offlineSync.report();
        }

        /* ----------------------------------------------------------- actions */

        handleClick(event) {
            const trigger = event.target.closest('[data-action]');

            if (! trigger || ! this.contains(trigger)) return;

            if (trigger.dataset.action === 'log') {
                event.preventDefault();
                this.log(trigger.closest('[data-set-row]'));
            }

            if (trigger.dataset.action === 'sync') {
                event.preventDefault();
                if (areen.offlineSync) areen.offlineSync.drain();
            }
        }

        log(row) {
            if (! row || ! areen.offlineStore || ! areen.offlineSync) return;

            // Step one, before anything else can fail: the round gets a name.
            if (! row.dataset.uuid) row.dataset.uuid = areen.offlineStore.uuid();

            const reps = row.querySelector('[data-reps]');
            const weight = row.querySelector('[data-weight]');

            const record = {
                client_uuid: row.dataset.uuid,
                program_exercise_id: Number.parseInt(row.dataset.programExercise, 10),
                performed_on: this.performedOn,
                set_number: Number.parseInt(row.dataset.setNumber, 10),
                reps_done: reps ? number(reps.value) : null,
                weight: weight ? number(weight.value) : null,
                is_completed: true,
                note: null,
                queued_at: Date.now(),
            };

            row.dataset.state = 'logged';
            row.dataset.sync = 'pending';

            this.optimistic.add(record.client_uuid);
            this.countLogged();

            areen.offlineSync.enqueue(record);

            const rest = Number.parseInt(row.dataset.rest || '0', 10);

            if (Number.isFinite(rest) && rest > 0) {
                window.dispatchEvent(new CustomEvent('areen:rest-start', { detail: { seconds: rest } }));
            }
        }

        /* ------------------------------------------------------------ readout */

        countLogged() {
            const done = this.rows().filter((row) => row.dataset.state === 'logged').length;
            const readout = this.querySelector('[data-logged-count]');
            const bar = this.querySelector('[data-progress-bar]');
            const total = this.rows().length;

            if (readout) readout.textContent = String(done);

            if (bar) {
                bar.style.inlineSize = total === 0 ? '0%' : `${Math.round((done / total) * 100)}%`;
                const meter = bar.closest('[role="progressbar"]');

                if (meter) meter.setAttribute('aria-valuenow', String(done));
            }
        }

        applySyncState(state) {
            const waiting = new Set(state.uuids || []);

            (state.accepted || []).forEach((uuid) => {
                const row = this.rowFor(uuid);

                if (row) row.dataset.sync = 'synced';

                this.optimistic.delete(uuid);
                waiting.delete(uuid);
            });

            (state.rejected || []).forEach((entry) => {
                const row = this.rowFor(entry.client_uuid);

                if (row) row.dataset.sync = 'rejected';

                this.optimistic.delete(entry.client_uuid);
                waiting.delete(entry.client_uuid);
            });

            /*
             * Anything the queue no longer holds and did not just fail has landed.
             * A round queued a moment ago is exempt: a report whose read of the
             * store started before the write would otherwise clear its dot before
             * it had been sent at all.
             */
            this.rows().forEach((row) => {
                if (row.dataset.state !== 'logged' || ! row.dataset.uuid) return;
                if (row.dataset.sync === 'rejected') return;

                const stillHere = waiting.has(row.dataset.uuid) || this.optimistic.has(row.dataset.uuid);

                row.dataset.sync = stillHere ? 'pending' : 'synced';
            });

            const status = this.querySelector('[data-sync-status]');

            if (status) {
                const pending = typeof state.pending === 'number' ? state.pending : waiting.size;

                status.dataset.pending = pending > 0 ? 'true' : 'false';

                const count = status.querySelector('[data-pending-count]');

                if (count) count.textContent = String(pending);
            }
        }

        /* --------------------------------------------------------- rest panel */

        showRest(visible, linger = false) {
            if (! this.restPanel) return;

            if (this.restHandle) {
                clearTimeout(this.restHandle);
                this.restHandle = null;
            }

            if (visible) {
                this.restPanel.hidden = false;

                return;
            }

            if (! linger) {
                this.restPanel.hidden = true;

                return;
            }

            this.restHandle = setTimeout(() => {
                this.restPanel.hidden = true;
                this.restHandle = null;
            }, REST_LINGER_MS);
        }
    }

    window.customElements.define('areen-set-logger', SetLogger);
})();
