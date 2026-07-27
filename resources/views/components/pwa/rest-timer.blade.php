@props([
    // The exercise's own rest, in seconds. `program_exercises.rest_seconds`.
    'seconds' => 90,
    // How much one tap of the "+" button adds.
    'extend' => 15,
])

@php
    $seconds = max(5, (int) $seconds);
    $extend = max(5, (int) $extend);

    // Painted server-side so the first frame already shows the right number.
    // Western digits everywhere (DESIGN.md §3), so no locale-aware formatting.
    $initial = intdiv($seconds, 60) . ':' . str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT);

    // Must match RING_RADIUS in resources/js/pwa/rest-timer.js.
    $ringLength = 2 * M_PI * 44;
@endphp

{{--
  The rest countdown, for the fixed bottom bar of the day page (DESIGN.md §11):
  48px tabular digits in ember inside a progress ring, and no modal — the trainee
  has one hand free and nothing here should need dismissing.

      <x-pwa.rest-timer :seconds="$programExercise->rest_seconds"/>

  It can also be started from anywhere without a reference to the element:

      window.dispatchEvent(new CustomEvent('areen:rest-start', { detail: { seconds: 90 } }))

  and it announces `areen:rest-started`, `areen:rest-cancelled` and
  `areen:rest-finished` as it goes.

  The countdown is a deadline, not a tally of ticks, so a locked screen cannot
  desynchronise it — see the header comment in the JavaScript for the three
  layers that make the finish land.
--}}

<areen-rest-timer
    data-seconds="{{ $seconds }}"
    data-extend="{{ $extend }}"
    data-state="idle"
    {{ $attributes->class('block rounded-lg border border-ink-700 bg-ink-800 p-5') }}>
    <p class="flex items-center justify-center gap-2 text-xs font-medium text-ink-300">
        <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <circle cx="12" cy="13" r="8"/>
            <path d="M12 9v4l2 2"/>
            <path d="M9 2h6"/>
        </svg>
        {{ __('pwa.rest.label') }}
    </p>

    <div class="relative mx-auto mt-3 grid size-[136px] place-items-center">
        <svg class="absolute inset-0 size-full" viewBox="0 0 100 100" aria-hidden="true" focusable="false">
            <circle cx="50" cy="50" r="44" fill="none" stroke="currentColor" stroke-width="5"
                    class="text-ink-700"/>
            <circle data-ring
                    cx="50" cy="50" r="44" fill="none" stroke="currentColor" stroke-width="5"
                    stroke-linecap="round"
                    stroke-dasharray="{{ $ringLength }}"
                    stroke-dashoffset="0"
                    transform="rotate(-90 50 50)"
                    class="text-ember transition-[stroke-dashoffset] duration-200 ease-linear"/>
        </svg>

        {{--
          `<output>` is implicitly a live region, which would make a screen reader
          read every repaint aloud. The number is silenced and the finish is
          announced once, below.
        --}}
        <output data-display
                aria-live="off"
                aria-label="{{ __('pwa.rest.remaining') }}"
                class="relative text-[48px] font-bold leading-none tabular text-ember">{{ $initial }}</output>
    </div>

    <div class="mt-4 flex items-center justify-center gap-2">
        <button type="button"
                data-action="toggle"
                class="inline-flex min-h-11 flex-1 items-center justify-center rounded-sm bg-brand-400
                       px-[18px] py-2.5 font-medium text-brand-950">
            <span data-label="start">{{ __('pwa.rest.start') }}</span>
            <span data-label="pause" hidden>{{ __('pwa.rest.pause') }}</span>
            <span data-label="resume" hidden>{{ __('pwa.rest.resume') }}</span>
        </button>

        <button type="button"
                data-action="extend"
                aria-label="{{ __('pwa.rest.extend', ['seconds' => $extend]) }}"
                class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-sm border
                       border-ink-600 px-3 py-2.5 text-sm font-medium tabular text-ink-200">
            +{{ $extend }}
        </button>

        <button type="button"
                data-action="reset"
                aria-label="{{ __('pwa.rest.reset') }}"
                class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-sm px-3 py-2.5
                       text-ink-300">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="M3 12a9 9 0 1 0 3-6.7L3 8"/>
                <path d="M3 3v5h5"/>
            </svg>
        </button>
    </div>

    <p data-done hidden role="status" aria-live="polite"
       class="mt-3 text-center text-sm font-medium text-success">
        {{ __('pwa.rest.done') }}
    </p>
</areen-rest-timer>
