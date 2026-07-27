{{--
  The standing "add to home screen" offer, for whoever dismissed the banner or
  never saw it.

  It hides itself inside the installed app and on any browser that cannot
  install, so it is never a button that does nothing. On iOS it opens the share
  sheet instructions instead of a prompt, because Safari exposes no install API
  and pretending otherwise would be worse than explaining.
--}}

<areen-install-button hidden {{ $attributes->class('contents') }}>
    <button type="button"
            data-action="install"
            class="inline-flex min-h-11 items-center gap-2 rounded-sm border border-ink-600 px-3
                   text-sm font-medium text-ink-200 transition-colors duration-150 ease-out
                   hover:bg-ink-800 print:hidden">
        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
             aria-hidden="true" focusable="false">
            <path d="M12 3v12"/>
            <path d="m8 11 4 4 4-4"/>
            <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
        </svg>

        <span class="whitespace-nowrap">{{ __('pwa.install.action') }}</span>
    </button>

    {{-- iOS only. `showModal()` traps focus and closes on Escape for free. --}}
    <dialog data-role="ios-guide"
            aria-labelledby="ios-guide-title"
            class="m-auto w-[min(92vw,26rem)] rounded-lg border border-ink-700 bg-ink-800 p-5
                   text-ink-100 backdrop:bg-ink-950/70">
        {{--
          Named through `aria-labelledby` rather than a heading element. This
          component sits in the header, so a real <h2> here would land before
          every page's <h1> and break the heading order of the whole site.
        --}}
        <p id="ios-guide-title" class="text-lg font-semibold text-ink-50">{{ __('pwa.install.ios_title') }}</p>

        <p class="mt-2 text-sm leading-relaxed text-ink-300">{{ __('pwa.install.ios_body') }}</p>

        <ol class="mt-4 flex flex-col gap-3 text-sm text-ink-200">
            <li class="flex items-start gap-3">
                <span class="tabular mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full
                             bg-ink-900 text-xs font-bold text-brand-400">1</span>
                <span>{{ __('pwa.install.ios_step_share') }}</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="tabular mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full
                             bg-ink-900 text-xs font-bold text-brand-400">2</span>
                <span>{{ __('pwa.install.ios_step_add') }}</span>
            </li>
        </ol>

        <button type="button"
                data-action="close-guide"
                class="mt-5 inline-flex min-h-11 w-full items-center justify-center rounded-sm
                       bg-brand-400 px-[18px] py-2.5 font-medium text-brand-950">
            {{ __('common.actions.close') }}
        </button>
    </dialog>
</areen-install-button>
