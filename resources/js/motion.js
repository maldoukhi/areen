/**
 * Scroll reveals and counting numbers for the public pages.
 *
 * Two rules shape this file. Nothing may be required to read the page — the
 * markup already contains every final value, and the CSS shows everything when
 * JavaScript is absent. And nothing runs for a visitor who asked for less
 * motion, which is checked live rather than once at load, because the setting
 * can change while the tab is open.
 */

const REVEAL_SELECTOR = '[data-reveal]';
const COUNT_SELECTOR = '[data-count-to]';

// Nothing stays hidden longer than this, whatever the observer does.
const FAILSAFE_MS = 1200;

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

/**
 * Marks the document so CSS can tell a scripted page from an unscripted one.
 * Set as early as possible so revealed elements never flash visible first.
 */
export function markScripted() {
    document.documentElement.classList.add('js');
}

function revealAll(root) {
    root.querySelectorAll(REVEAL_SELECTOR).forEach((el) => {
        el.dataset.revealed = 'true';
        delete el.dataset.armed;
    });
}

function observeReveals(root) {
    if (reducedMotion.matches || ! ('IntersectionObserver' in window)) {
        return revealAll(root);
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (! entry.isIntersecting) return;

                entry.target.dataset.revealed = 'true';
                observer.unobserve(entry.target);
            });
        },
        // A little before the element arrives, so the motion finishes as it lands.
        { rootMargin: '0px 0px -8% 0px', threshold: 0.05 },
    );

    const armed = [];

    root.querySelectorAll(REVEAL_SELECTOR).forEach((el, index) => {
        if (el.dataset.revealed === 'true') return;

        // Staggering by position reads as one movement rather than several.
        el.style.setProperty('--reveal-delay', `${Math.min(index, 6) * 60}ms`);
        // Hiding starts here, so an element is never hidden by anything that is
        // not already committed to showing it again.
        el.dataset.armed = 'true';
        armed.push(el);
        observer.observe(el);
    });

    /*
     * A last resort. If the observer never reports — a browser quirk, a
     * container that never scrolls, a page restored from the back/forward
     * cache — the content appears anyway rather than staying invisible.
     */
    window.setTimeout(() => {
        armed.forEach((el) => {
            if (el.dataset.revealed !== 'true') el.dataset.revealed = 'true';
        });
    }, FAILSAFE_MS);
}

function countUp(el) {
    const target = Number.parseInt(el.dataset.countTo ?? '', 10);
    if (! Number.isFinite(target)) return;

    if (reducedMotion.matches) {
        el.textContent = String(target);

        return;
    }

    const duration = 900;
    const start = performance.now();

    const step = (now) => {
        const progress = Math.min(1, (now - start) / duration);
        // Ease out: the number slows into its value instead of stopping dead.
        const eased = 1 - (1 - progress) ** 3;

        el.textContent = String(Math.round(target * eased));

        if (progress < 1) requestAnimationFrame(step);
    };

    requestAnimationFrame(step);
}

function observeCounters(root) {
    const counters = root.querySelectorAll(COUNT_SELECTOR);
    if (counters.length === 0) return;

    if (! ('IntersectionObserver' in window)) {
        return counters.forEach(countUp);
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (! entry.isIntersecting) return;

                countUp(entry.target);
                observer.unobserve(entry.target);
            });
        },
        { threshold: 0.4 },
    );

    counters.forEach((el) => observer.observe(el));
}

export function startMotion(root = document) {
    observeReveals(root);
    observeCounters(root);
}

/**
 * `wire:navigate` swaps the body without a reload, so the observers have to be
 * rebuilt against the new markup each time.
 */
export function watchNavigation() {
    document.addEventListener('livewire:navigated', () => startMotion(document));
}
