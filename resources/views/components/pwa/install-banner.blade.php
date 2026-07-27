{{--
  The install invitation, in the club's own voice.

  Two variants live in the markup and JavaScript reveals exactly one:

    prompt — Chromium fired `beforeinstallprompt`, we cancelled the browser's
             own infobar, and the button hands the event back to it.
    ios    — Safari never fires that event and offers no API at all, so the only
             honest thing to show is where the share sheet is.

  The banner starts `hidden` and is only ever revealed by
  `resources/js/pwa/install-banner.js`, which refuses to show it inside the
  installed app or after a dismissal. Nothing here is visible on a first paint.

  It sits at the bottom because that is where the thumb is (DESIGN.md §11), and
  the browser bottom bar is exactly what `safe-pb` clears.
--}}

<areen-install-banner hidden
    class="fixed inset-x-0 bottom-0 z-[55] block px-4 safe-pb print:hidden"
    role="region"
    aria-label="{{ __('pwa.install.title') }}">
    <div class="mx-auto w-full max-w-[520px] rounded-lg border border-ink-700 bg-ink-800 p-5">
        <div class="flex items-start gap-3">
            <x-brand.club-logo height="h-8" mark-class="mt-0.5 size-8 shrink-0 text-brand-400" class="mt-0.5 shrink-0"/>

            <div class="min-w-0 flex-1">
                <div data-variant="prompt" hidden>
                    <p class="font-semibold leading-snug text-ink-50">{{ __('pwa.install.title') }}</p>
                    <p class="mt-1 text-sm leading-relaxed text-ink-300">{{ __('pwa.install.body') }}</p>
                </div>

                <div data-variant="ios" hidden>
                    <p class="font-semibold leading-snug text-ink-50">{{ __('pwa.install.ios_title') }}</p>
                    <p class="mt-1 flex items-start gap-1.5 text-sm leading-relaxed text-ink-300">
                        {{-- The iOS share glyph, so the sentence points at something they can see. --}}
                        <svg class="mt-1 size-4 shrink-0 text-brand-400" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true" focusable="false">
                            <path d="M12 3v12"/>
                            <path d="m8 7 4-4 4 4"/>
                            <path d="M4 13v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-6"/>
                        </svg>
                        <span>{{ __('pwa.install.ios_body') }}</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <button type="button"
                    data-action="install"
                    data-variant="prompt"
                    hidden
                    class="inline-flex min-h-11 flex-1 items-center justify-center rounded-sm bg-brand-400
                           px-[18px] py-2.5 font-medium text-brand-950">
                {{ __('pwa.install.action') }}
            </button>

            <button type="button"
                    data-action="dismiss"
                    class="inline-flex min-h-11 items-center justify-center rounded-sm border border-ink-600
                           px-4 py-2.5 text-sm font-medium text-ink-200">
                {{ __('pwa.install.dismiss') }}
            </button>
        </div>
    </div>
</areen-install-banner>
