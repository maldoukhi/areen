import { registerServiceWorker } from './pwa/register-sw';
import { markDisplayMode } from './pwa/standalone';
import { defineInstallBanner } from './pwa/install-banner';
import { defineUpdateBar } from './pwa/update-bar';
import { defineWakeLockToggle } from './pwa/wake-lock-toggle';
import { defineRestTimer } from './pwa/rest-timer';
import { watchProgramScope } from './pwa/offline-scope';

registerServiceWorker();
markDisplayMode();

/*
 * The PWA widgets are custom elements rather than Alpine components on purpose.
 * Alpine arrives with Livewire's own script, which loads at the end of the body,
 * while this module runs from the head — registering `Alpine.data` from here is
 * a race. A custom element upgrades whenever the browser meets the tag, in any
 * order, and survives a `wire:navigate` swap without re-registration.
 *
 * Every one of them renders its text from Blade and reads it out of the DOM, so
 * nothing here needs a translation of its own.
 */
defineInstallBanner();
defineUpdateBar();
defineWakeLockToggle();
defineRestTimer();

watchProgramScope();
