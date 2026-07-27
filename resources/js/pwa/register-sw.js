/**
 * Service worker registration.
 *
 * The worker is registered from the site root so its scope covers the whole
 * origin — `vite.config.js` lifts the generated file out of `public/build`
 * for exactly this reason.
 *
 * Updates are never applied silently. A new worker waits until the visitor
 * accepts, because swapping assets out mid-session while somebody is logging
 * a set is how you lose their set.
 */

const SW_URL = '/sw.js';

export function registerServiceWorker() {
    if (! ('serviceWorker' in navigator)) return;
    if (import.meta.env.DEV) return;

    window.addEventListener('load', async () => {
        try {
            const registration = await navigator.serviceWorker.register(SW_URL, { scope: '/' });

            if (registration.waiting) {
                announceUpdate(registration);
            }

            registration.addEventListener('updatefound', () => {
                const installing = registration.installing;
                if (! installing) return;

                installing.addEventListener('statechange', () => {
                    // `controller` is null on the very first install — that is not an update.
                    if (installing.state === 'installed' && navigator.serviceWorker.controller) {
                        announceUpdate(registration);
                    }
                });
            });
        } catch (error) {
            // A failed registration must never take the page down with it.
            console.warn('[areen] service worker registration failed', error);
        }
    });

    let reloading = false;
    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (reloading) return;
        reloading = true;
        window.location.reload();
    });
}

function announceUpdate(registration) {
    window.dispatchEvent(
        new CustomEvent('areen:update-available', {
            detail: {
                apply: () => registration.waiting?.postMessage({ type: 'SKIP_WAITING' }),
            },
        }),
    );
}
