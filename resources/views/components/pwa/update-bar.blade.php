{{--
  "A new version is ready" — shown, never applied behind the trainee's back.

  `resources/js/pwa/register-sw.js` registers the worker with
  `registerType: 'prompt'` and fires `areen:update-available` once a replacement
  is installed and waiting. This bar is the only thing that can let it through,
  and only on a tap: swapping the app out mid-set is how a set gets lost.

  In standalone mode the bottom nav owns the bottom of the screen, so the bar
  lifts by the nav's 60px; its own `safe-pb` already covers the gesture area, and
  60px + that padding lands exactly on the nav's top edge.
--}}

<areen-update-bar hidden
    class="fixed inset-x-0 bottom-0 z-[55] block px-4 safe-pb print:hidden
           [@media(display-mode:standalone)]:bottom-[60px]"
    role="status"
    aria-live="polite">
    <div class="mx-auto flex w-full max-w-[520px] items-center gap-3 rounded-lg border border-ink-700
                bg-ink-800 p-4">
        <svg class="size-5 shrink-0 text-brand-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <path d="M3 12a9 9 0 0 1 15.5-6.2L21 8"/>
            <path d="M21 3v5h-5"/>
            <path d="M21 12a9 9 0 0 1-15.5 6.2L3 16"/>
            <path d="M3 21v-5h5"/>
        </svg>

        <p class="min-w-0 flex-1 text-sm leading-snug text-ink-100">{{ __('pwa.update.body') }}</p>

        <button type="button"
                data-action="apply"
                class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-sm bg-brand-400
                       px-[18px] py-2.5 text-sm font-medium text-brand-950">
            <span data-label="apply">{{ __('pwa.update.action') }}</span>
            <span data-label="applying" hidden>{{ __('pwa.update.applying') }}</span>
        </button>

        <button type="button"
                data-action="dismiss"
                aria-label="{{ __('pwa.update.dismiss') }}"
                class="inline-flex size-11 shrink-0 items-center justify-center rounded-sm text-ink-400">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                 stroke-linecap="round" aria-hidden="true" focusable="false">
                <path d="M18 6 6 18M6 6l12 12"/>
            </svg>
        </button>
    </div>
</areen-update-bar>
