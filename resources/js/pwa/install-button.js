/**
 * `<areen-install-button>` — a standing offer to add the app to the home screen.
 *
 * The banner asks once and can be dismissed. This is the button someone goes
 * looking for afterwards, so it lives in the header and stays until there is
 * nothing left to offer.
 *
 * It is hidden in three cases, all of them cases where a button would be a lie:
 * inside the installed app, on a browser that cannot install at all, and on
 * Android before `beforeinstallprompt` has arrived. On iOS it is shown but opens
 * instructions instead of a prompt, because Safari exposes no way to install and
 * the share sheet is the only route.
 */

import { isIOS } from './platform.js';
import { isInstalled } from './standalone.js';
import { canPrompt, onInstallStateChange, promptInstall } from './install-prompt.js';

class InstallButton extends HTMLElement {
    connectedCallback() {
        this.trigger = this.querySelector('[data-action="install"]');
        this.guide = this.querySelector('[data-role="ios-guide"]');
        this.closeGuide = this.querySelector('[data-action="close-guide"]');

        this.trigger?.addEventListener('click', () => this.activate());
        this.closeGuide?.addEventListener('click', () => this.guide?.close());

        // Tapping the backdrop closes it; a dialog reports that as a click on itself.
        this.guide?.addEventListener('click', (event) => {
            if (event.target === this.guide) this.guide.close();
        });

        this.stopWatching = onInstallStateChange(() => this.decide());

        this.decide();
    }

    disconnectedCallback() {
        this.stopWatching?.();
    }

    decide() {
        this.hidden = isInstalled() || (! canPrompt() && ! isIOS());
    }

    async activate() {
        if (isIOS()) {
            // `showModal` traps focus and handles Escape; a plain open() does not.
            this.guide?.showModal();

            return;
        }

        await promptInstall();

        // Spent either way: a declined prompt cannot be reopened by us.
        this.decide();
    }
}

export function defineInstallButton() {
    if (! ('customElements' in window)) return;
    if (customElements.get('areen-install-button')) return;

    customElements.define('areen-install-button', InstallButton);
}
