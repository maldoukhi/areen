{{--
  "Keep the screen awake" (DESIGN.md §11) — the switch that belongs at the top of
  the day page.

      <x-pwa.wake-lock-toggle/>

  Rendered hidden and revealed only by a browser that actually has the Screen
  Wake Lock API; Safari before 16.4 and every older Android simply never see it,
  which is better than a switch that flips and does nothing.

  The lock itself is reference-counted in `resources/js/pwa/wake-lock.js`, so
  this switch and the running rest timer can both want the screen on without
  either one taking it away from the other.
--}}

<areen-wake-lock hidden {{ $attributes->class('group block') }} data-state="off">
    <button type="button"
            data-action="toggle"
            role="switch"
            aria-checked="false"
            class="inline-flex min-h-11 w-full items-center gap-3 rounded-sm px-2 py-2 text-start
                   text-sm font-medium text-ink-200">
        <svg class="size-5 shrink-0 text-ink-300 transition-colors duration-150 ease-out
                    group-data-[state=on]:text-brand-400"
             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <circle cx="12" cy="12" r="4"/>
            <path d="M12 2v2"/>
            <path d="M12 20v2"/>
            <path d="m4.93 4.93 1.41 1.41"/>
            <path d="m17.66 17.66 1.41 1.41"/>
            <path d="M2 12h2"/>
            <path d="M20 12h2"/>
            <path d="m6.34 17.66-1.41 1.41"/>
            <path d="m19.07 4.93-1.41 1.41"/>
        </svg>

        <span class="flex-1">{{ __('pwa.wake_lock.label') }}</span>

        {{--
          The knob moves with `justify-end` rather than a translate, so it slides
          towards the trailing edge in both RTL and LTR without a physical offset.
        --}}
        <span class="flex h-6 w-11 shrink-0 items-center rounded-full bg-ink-700 p-0.5
                     transition-colors duration-150 ease-out
                     group-data-[state=on]:justify-end group-data-[state=on]:bg-brand-400"
              aria-hidden="true">
            <span class="size-5 rounded-full bg-ink-300 transition-colors duration-150 ease-out
                         group-data-[state=on]:bg-brand-950"></span>
        </span>
    </button>
</areen-wake-lock>
