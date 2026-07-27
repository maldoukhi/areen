/**
 * Draining the queue — the half of offline logging that talks to the server.
 *
 * ## Why the drain is triggered from four places, not one
 *
 * Background Sync (`registration.sync.register`) is the right primitive: the
 * browser holds the tag and wakes the service worker when connectivity returns,
 * even if the app was closed on the walk home. It exists on Chromium only.
 * **Safari on iOS has never shipped it**, and iOS is a large share of the phones
 * that will open this app. So the tag is registered where it is available and is
 * treated as a bonus, never as the mechanism:
 *
 *   1. on load — the queue from last night's session goes out as soon as the app
 *      is opened on a connection;
 *   2. on `online` — the moment the phone reports signal again, which on iOS is
 *      the event that actually does the work;
 *   3. on `visibilitychange` — `online` does not always fire when a phone comes
 *      out of a pocket onto wifi, but the page becoming visible always does;
 *   4. on `pagehide`, with `keepalive`, so a queue built during a session still
 *      leaves as the trainee closes the app;
 *
 * plus a backoff retry while anything is still pending, because a captive-portal
 * wifi answers the `online` event and then refuses the request.
 *
 * ## Why a failed drain is not an error
 *
 * Nothing here surfaces a failure as something the trainee must act on. The
 * round is already saved; the only question is when it leaves the phone. The
 * screen shows how many rounds are waiting, and that is the whole report.
 *
 * ## Why rejected rounds are dropped rather than retried
 *
 * A round the server refuses — its exercise is not on this account's program, or
 * its uuid belongs to somebody else — will be refused identically forever.
 * Keeping it would mean retrying it on every drain for the life of the install,
 * so it is removed from the queue and the row is marked, once.
 */

(() => {
    const areen = (window.Areen = window.Areen || {});

    if (areen.offlineSync) return;

    const SYNC_TAG = 'areen-workout-logs';
    const BATCH_SIZE = 100;
    const RETRY_BASE_MS = 8000;
    const RETRY_MAX_MS = 5 * 60 * 1000;

    let draining = false;
    let drainAgain = false;
    let failures = 0;
    let retryHandle = null;

    /**
     * Endpoint and CSRF token are rendered into the page by the Blade component
     * rather than baked in here, so the module has no idea what the routes are
     * called and a page that does not offer logging simply never drains.
     */
    function config() {
        const node = document.querySelector('[data-areen-sync]');

        if (! node) return null;

        const endpoint = node.getAttribute('data-endpoint');

        return endpoint ? { endpoint, token: node.getAttribute('data-csrf') || '' } : null;
    }

    function announce(detail) {
        window.dispatchEvent(new CustomEvent('areen:sync-state', { detail }));
    }

    async function report(extra = {}) {
        const pending = await areen.offlineStore.all();

        announce({
            pending: pending.length,
            uuids: pending.map((row) => row.client_uuid),
            syncing: draining,
            online: navigator.onLine !== false,
            ...extra,
        });
    }

    function payload(record) {
        return {
            client_uuid: record.client_uuid,
            program_exercise_id: record.program_exercise_id,
            performed_on: record.performed_on,
            set_number: record.set_number,
            reps_done: record.reps_done,
            weight: record.weight,
            is_completed: record.is_completed !== false,
            note: record.note || null,
        };
    }

    /**
     * A 422 names its offenders by position — `logs.3.reps_done`. Those rounds
     * are malformed and will stay malformed, so they are dropped by index and the
     * rest of the batch goes again on the next drain rather than being held
     * hostage by one bad row.
     */
    function malformedIndexes(body) {
        const errors = (body && body.errors) || {};
        const found = new Set();

        Object.keys(errors).forEach((key) => {
            const match = /^logs\.(\d+)\b/.exec(key);

            if (match) found.add(Number.parseInt(match[1], 10));
        });

        return found;
    }

    async function send(batch, settings, keepalive) {
        const response = await fetch(settings.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': settings.token,
            },
            body: JSON.stringify({ logs: batch.map(payload) }),
        });

        if (response.status === 422) {
            const body = await response.json().catch(() => null);
            const indexes = malformedIndexes(body);

            const dropped = [...indexes]
                .map((index) => batch[index] && batch[index].client_uuid)
                .filter(Boolean);

            if (dropped.length) {
                await areen.offlineStore.remove(dropped);
                announce({ rejected: dropped.map((uuid) => ({ client_uuid: uuid, reason: 'invalid' })) });
            }

            // Nothing was accepted, but something was learned. Not a failure.
            return { accepted: [], rejected: [] };
        }

        if (! response.ok) {
            throw new Error(`areen: sync responded ${response.status}`);
        }

        const body = await response.json();
        const accepted = Array.isArray(body.accepted) ? body.accepted : [];
        const rejected = Array.isArray(body.rejected) ? body.rejected : [];

        // Both lists are settled: one landed, the other never will.
        await areen.offlineStore.remove([...accepted, ...rejected.map((row) => row.client_uuid)]);

        announce({ accepted, rejected });

        return { accepted, rejected };
    }

    function scheduleRetry() {
        if (retryHandle) return;

        const delay = Math.min(RETRY_MAX_MS, RETRY_BASE_MS * 2 ** Math.min(failures, 5));

        retryHandle = setTimeout(() => {
            retryHandle = null;
            drain();
        }, delay);
    }

    async function drain(options = {}) {
        const keepalive = options.keepalive === true;
        const settings = config();

        if (! settings) return;

        if (draining) {
            drainAgain = true;

            return;
        }

        draining = true;

        try {
            const pending = await areen.offlineStore.all();

            if (pending.length === 0) {
                failures = 0;

                return;
            }

            // `navigator.onLine` lies in one direction only: false is reliable,
            // true is a guess. Trusting the reliable half saves a doomed request.
            if (navigator.onLine === false) {
                await requestBackgroundSync();

                return;
            }

            await report({ syncing: true });

            for (let index = 0; index < pending.length; index += BATCH_SIZE) {
                await send(pending.slice(index, index + BATCH_SIZE), settings, keepalive);
            }

            failures = 0;
        } catch {
            // Offline, a captive portal, an expired session, a 500. The rounds
            // stay queued and the phone tries again.
            failures += 1;
            await requestBackgroundSync();
            scheduleRetry();
        } finally {
            draining = false;

            await report();

            if (drainAgain) {
                drainAgain = false;
                drain();
            }
        }
    }

    /**
     * Best effort, and deliberately quiet. Where Background Sync exists this
     * hands the queue to the browser so it goes out with the app closed; where it
     * does not — every iPhone — the four in-page triggers already cover it.
     */
    async function requestBackgroundSync() {
        try {
            if (! ('serviceWorker' in navigator)) return;

            const registration = await navigator.serviceWorker.ready;

            if (! registration || ! ('sync' in registration)) return;

            await registration.sync.register(SYNC_TAG);
        } catch {
            // Permission denied, or the tag limit reached. Not worth a word.
        }
    }

    async function enqueue(record) {
        await areen.offlineStore.put(record);
        await report();

        requestBackgroundSync();
        drain();

        return record;
    }

    function listen() {
        window.addEventListener('online', () => {
            failures = 0;

            if (retryHandle) {
                clearTimeout(retryHandle);
                retryHandle = null;
            }

            drain();
        });

        window.addEventListener('offline', () => report());

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') drain();
        });

        // A body swap keeps the JavaScript alive but replaces the sync node, so
        // the readout has to be repainted and the queue re-checked.
        document.addEventListener('livewire:navigated', () => drain());

        window.addEventListener('pagehide', () => drain({ keepalive: true }));

        // If a future service worker gains a `sync` handler it can ask an open
        // client to do the work, which is cheaper than duplicating the logic.
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('message', (event) => {
                if (event.data && event.data.type === 'areen:drain-logs') drain();
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => drain(), { once: true });
        } else {
            drain();
        }
    }

    areen.offlineSync = { enqueue, drain, report, tag: SYNC_TAG };

    listen();
})();
