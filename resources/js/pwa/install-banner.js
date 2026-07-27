/**
 * `<areen-install-banner>` — the invitation to install the app.
 *
 * Two paths, because the platforms do not agree:
 *
 *  - Android / Chromium fires `beforeinstallprompt`. We swallow the browser's
 *    own mini-infobar and show the club's card instead, so the ask arrives in
 *    the app's own voice and within thumb reach.
 *  - iOS never fires it, and has no API to trigger installation at all. The only
 *    route is Share → Add to Home Screen, so the card explains that instead of
 *    offering a button that could not do anything.
 *
 * Neither is ever shown inside the installed app, and a dismissal is remembered.
 *
 * The markup lives in `resources/views/components/pwa/install-banner.blade.php`
 * so every word goes through `__()`. This file only decides what is visible.
 */

import { isIOS, readFlag, writeFlag } from './platform.js';
// Display-mode detection lives in standalone.js; platform.js owns storage and UA.
import { isInstalled } from './standalone.js';

const DISMISSED_KEY = 'areen:install-dismissed';

/*
 * `beforeinstallprompt` can fire before the custom element upgrades, and the
 * event is only useful if it was cancelled at the moment it fired. So it is
 * caught here at module scope — this file is imported from the entry point,
 * which runs before the page settles — and parked for whoever asks later.
 */
let deferredPrompt = null;

if (typeof window !== 'undefined') {
    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;

        window.dispatchEvent(new CustomEvent('areen:install-available'));
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        // Installed is the strongest possible dismissal.
        writeFlag(DISMISSED_KEY, String(Date.now()));
    });
}

class InstallBanner extends HTMLElement {
    connectedCallback() {
        this.installButton = this.querySelector('[data-action="install"]');
        this.dismissButton = this.querySelector('[data-action="dismiss"]');

        this.installButton?.addEventListener('click', () => this.install());
        this.dismissButton?.addEventListener('click', () => this.dismiss());

        this.onAvailable = () => this.decide();
        this.onInstalled = () => this.hide();
        // An update is more urgent than an install, and two stacked bars at the
        // bottom of a 360px screen is one bar too many.
        this.onUpdate = () => this.hide();

        window.addEventListener('areen:install-available', this.onAvailable);
        window.addEventListener('appinstalled', this.onInstalled);
        window.addEventListener('areen:update-available', this.onUpdate);

        this.decide();
    }

    disconnectedCallback() {
        window.removeEventListener('areen:install-available', this.onAvailable);
        window.removeEventListener('appinstalled', this.onInstalled);
        window.removeEventListener('areen:update-available', this.onUpdate);
    }

    decide() {
        // Already the app, or already told us no.
        if (isInstalled() || readFlag(DISMISSED_KEY)) return this.hide();

        if (deferredPrompt) return this.show('prompt');

        // No event yet. On iOS there never will be one, so the share-sheet
        // instructions are the whole offer. Everywhere else we wait quietly —
        // showing an install card the browser has not blessed usually means the
        // app does not qualify for installation yet.
        if (isIOS()) return this.show('ios');

        return this.hide();
    }

    show(variant) {
        this.dataset.variant = variant;

        this.querySelectorAll('[data-variant]').forEach((node) => {
            node.hidden = node.dataset.variant !== variant;
        });

        this.hidden = false;
    }

    hide() {
        this.hidden = true;
    }

    async install() {
        if (! deferredPrompt) return this.hide();

        const prompt = deferredPrompt;
        // The event is single-use; a second `prompt()` call throws.
        deferredPrompt = null;

        this.hidden = true;

        try {
            await prompt.prompt();
            const choice = await prompt.userChoice;

            if (choice?.outcome === 'dismissed') {
                // Declining the system sheet is a real answer. Do not re-ask.
                writeFlag(DISMISSED_KEY, String(Date.now()));
            }
        } catch {
            // The sheet failed to open. Leave the banner hidden for this visit
            // rather than flashing it back at somebody who just tapped it.
        }
    }

    dismiss() {
        writeFlag(DISMISSED_KEY, String(Date.now()));
        this.hide();
    }
}

export function defineInstallBanner() {
    if (! ('customElements' in window)) return;
    if (customElements.get('areen-install-banner')) return;

    customElements.define('areen-install-banner', InstallBanner);
}
