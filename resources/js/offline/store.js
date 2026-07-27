/**
 * The offline queue — one IndexedDB object store, keyed by `client_uuid`.
 *
 * A set logged in a basement has to survive three things: no signal, a locked
 * phone, and the browser deciding to kill the tab. `localStorage` survives the
 * first two but is synchronous and small; IndexedDB survives all three, is
 * asynchronous, and — the part that matters here — lets the *key* be the round's
 * own identity rather than a position in a list.
 *
 * Keying on `client_uuid` is what makes the whole pipeline idempotent from end to
 * end. Writing the same round twice replaces one record instead of appending a
 * second, so a double tap, a re-render, or a retry that raced with an acceptance
 * can never grow the queue. The server upserts on the same column, so the two
 * halves agree on what "the same round" means without exchanging ids.
 *
 * Everything here refuses to throw. A browser in private mode, a locked-down
 * profile, or a full disk makes `indexedDB` unavailable or unopenable, and a
 * trainee mid-set must not discover this as a broken button — the queue falls
 * back to memory, which still gets them through the session with the network
 * attempt happening on every log.
 */

(() => {
    const areen = (window.Areen = window.Areen || {});

    // Re-executed by a `wire:navigate` body swap. The open database, and any
    // records already queued in the memory fallback, must survive that.
    if (areen.offlineStore) return;

    const DB_NAME = 'areen-offline';
    const DB_VERSION = 1;
    const STORE = 'workout-logs';

    const memory = new Map();
    let memoryOnly = false;
    let opening = null;

    function unavailable() {
        memoryOnly = true;
        opening = null;

        return null;
    }

    function open() {
        if (memoryOnly) return Promise.resolve(null);
        if (opening) return opening;

        opening = new Promise((resolve) => {
            let request;

            try {
                if (! window.indexedDB) return resolve(unavailable());

                request = window.indexedDB.open(DB_NAME, DB_VERSION);
            } catch {
                return resolve(unavailable());
            }

            request.onupgradeneeded = () => {
                const db = request.result;

                if (! db.objectStoreNames.contains(STORE)) {
                    db.createObjectStore(STORE, { keyPath: 'client_uuid' });
                }
            };

            request.onsuccess = () => {
                const db = request.result;

                // A second tab asking for a newer version would otherwise wedge
                // this one open forever.
                db.onversionchange = () => {
                    db.close();
                    opening = null;
                };

                resolve(db);
            };

            request.onerror = () => resolve(unavailable());
            request.onblocked = () => resolve(unavailable());
        });

        return opening;
    }

    function transact(mode, work) {
        return open().then((db) => {
            if (! db) return null;

            return new Promise((resolve, reject) => {
                let transaction;

                try {
                    transaction = db.transaction(STORE, mode);
                } catch (error) {
                    return reject(error);
                }

                const request = work(transaction.objectStore(STORE));

                transaction.onabort = () => reject(transaction.error);
                transaction.onerror = () => reject(transaction.error);
                transaction.oncomplete = () => resolve(request ? request.result : null);
            });
        });
    }

    /**
     * A crypto-grade `client_uuid`, generated on the phone before the round is
     * ever sent. `crypto.randomUUID` needs a secure context, so a phone on a
     * plain-HTTP staging host falls back to random bytes shaped into a v4 uuid —
     * and, failing even that, to `Math.random`, which is weak but still unique
     * enough for one device's own queue.
     */
    function uuid() {
        const crypto = window.crypto || window.msCrypto;

        if (crypto && typeof crypto.randomUUID === 'function') {
            try {
                return crypto.randomUUID();
            } catch {
                // Insecure context. Fall through.
            }
        }

        const bytes = new Uint8Array(16);

        if (crypto && typeof crypto.getRandomValues === 'function') {
            crypto.getRandomValues(bytes);
        } else {
            for (let index = 0; index < 16; index++) bytes[index] = Math.floor(Math.random() * 256);
        }

        bytes[6] = (bytes[6] & 0x0f) | 0x40;
        bytes[8] = (bytes[8] & 0x3f) | 0x80;

        const hex = [...bytes].map((byte) => byte.toString(16).padStart(2, '0'));

        return [
            hex.slice(0, 4).join(''),
            hex.slice(4, 6).join(''),
            hex.slice(6, 8).join(''),
            hex.slice(8, 10).join(''),
            hex.slice(10, 16).join(''),
        ].join('-');
    }

    async function put(record) {
        memory.set(record.client_uuid, record);

        try {
            await transact('readwrite', (store) => store.put(record));
        } catch {
            memoryOnly = true;
        }

        return record;
    }

    async function all() {
        try {
            const rows = await transact('readonly', (store) => store.getAll());

            if (Array.isArray(rows)) {
                // The database is the record of truth once it answers; memory is
                // only a mirror so a failed write is not silently lost.
                memory.clear();
                rows.forEach((row) => memory.set(row.client_uuid, row));

                return rows;
            }
        } catch {
            memoryOnly = true;
        }

        return [...memory.values()];
    }

    async function remove(uuids) {
        const list = Array.isArray(uuids) ? uuids : [uuids];

        list.forEach((id) => memory.delete(id));

        try {
            await transact('readwrite', (store) => {
                list.forEach((id) => store.delete(id));

                return null;
            });
        } catch {
            memoryOnly = true;
        }
    }

    async function count() {
        return (await all()).length;
    }

    areen.offlineStore = { put, all, remove, count, uuid };
})();
