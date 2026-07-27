@props(['program'])

{{--
  The whole program overview. `programs.show` and `programs.private` both render
  this and nothing else of their own, so the public door and the coded door can
  never drift apart — the only difference between them is who is allowed in.

  The component loads its own days rather than trusting the caller to eager load
  them, which is what keeps the two pages honest and keeps the query count flat.
--}}

@php
    $days = $program->days()->with('focusMuscle')->withCount('exercises')->get();

    // The action offers the first day that actually asks for work; a program
    // that opens on a rest day should not start the trainee on nothing.
    $firstDay = $days->firstWhere('is_rest_day', false) ?? $days->first();

    $exerciseCount = (int) $days->sum('exercises_count');
@endphp

<div {{ $attributes->class('flex flex-col gap-8 px-4 pt-6 pb-4') }}>
    <header class="flex flex-col gap-4">
        <x-program.tags :program="$program">
            @unless ($program->is_public)
                <x-ui.chip :active="true">{{ __('program.access.badge') }}</x-ui.chip>
            @endunless
        </x-program.tags>

        <h1 class="text-[2rem] font-bold leading-tight text-ink-50">{{ $program->name }}</h1>

        <div class="flex flex-wrap items-end gap-x-10 gap-y-4">
            <x-ui.metric
                size="lg"
                :value="$program->days_count"
                :caption="__('program.days.label')"/>

            @if ($exerciseCount > 0)
                <x-ui.metric
                    size="lg"
                    tone="ink"
                    :value="$exerciseCount"
                    :caption="__('exercise.title')"/>
            @endif
        </div>
    </header>

    @if (filled($program->description))
        <section class="flex flex-col gap-3">
            <h2 class="text-xl font-semibold text-ink-50">{{ __('program.meta.about') }}</h2>
            <p class="max-w-[65ch] text-ink-200">{{ $program->description }}</p>
        </section>
    @endif

    <section class="flex flex-col gap-3">
        <h2 class="text-xl font-semibold text-ink-50">{{ __('program.days.label') }}</h2>

        @if ($days->isEmpty())
            <x-ui.empty-state>
                <x-slot:title>{{ __('program.days.none_title') }}</x-slot:title>
                <x-slot:body>{{ __('program.days.none_body') }}</x-slot:body>
                <x-slot:action>
                    <x-ui.button variant="secondary" :href="route('programs.index')" wire:navigate>
                        {{ __('program.actions.browse') }}
                    </x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        @else
            <ol class="flex flex-col gap-3">
                @foreach ($days as $day)
                    @php
                        // A day may have no title at all — the columns are nullable —
                        // so it falls back to its own number rather than to a blank row.
                        $dayTitle = filled($day->title)
                            ? $day->title
                            : __('program.days.number', ['number' => $day->day_number]);

                        $dayMeta = array_filter([
                            $day->focusMuscle?->name,
                            $day->is_rest_day
                                ? __('program.days.rest')
                                : __('program.days.exercises_count', ['count' => $day->exercises_count]),
                        ]);
                    @endphp

                    <li wire:key="program-day-{{ $day->getKey() }}">
                        <a href="{{ route('programs.day', [$program, $day->day_number]) }}"
                           wire:navigate
                           @class([
                               'flex min-h-16 items-center gap-4 rounded-md border p-3 text-start',
                               'transition-colors duration-150 ease-out hover:border-brand-400/40',
                               'border-ink-700 bg-ink-800' => ! $day->is_rest_day,
                               // A rest day sinks into the page instead of standing on it.
                               'border-ink-800 bg-ink-900' => $day->is_rest_day,
                           ])>
                            <span @class([
                                'flex size-11 shrink-0 items-center justify-center rounded-md bg-ink-950',
                                'tabular text-xl font-bold leading-none',
                                'text-brand-400' => ! $day->is_rest_day,
                                'text-ink-400' => $day->is_rest_day,
                            ])>{{ $day->day_number }}</span>

                            <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                                <span @class([
                                    'truncate text-base font-semibold leading-snug',
                                    'text-ink-50' => ! $day->is_rest_day,
                                    'text-ink-300' => $day->is_rest_day,
                                ])>{{ $dayTitle }}</span>

                                @if ($dayMeta !== [])
                                    <span class="tabular truncate text-sm leading-normal text-ink-400">
                                        {{ implode(' · ', $dayMeta) }}
                                    </span>
                                @endif
                            </span>
                        </a>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>

    {{--
      DESIGN.md §11: the one primary action stays within thumb reach. Sticking it
      to the bottom of the viewport keeps it in the lower half of a 360px screen
      however long the day list grows, and it settles into the flow at the end of
      the page. In standalone mode it rides above the bottom bar, and it clears
      the gesture area in both.
    --}}
    @if ($firstDay)
        <div class="sticky bottom-0 z-30 -mx-4 border-t border-ink-800 bg-ink-950/90 px-4 pt-3
                    pb-[calc(16px+env(safe-area-inset-bottom))] backdrop-blur-md
                    [@media(display-mode:standalone)]:bottom-[calc(60px+16px+env(safe-area-inset-bottom))]
                    [@media(display-mode:standalone)]:pb-4
                    print:hidden">
            <x-ui.button
                full
                wire:navigate
                :href="route('programs.day', [$program, $firstDay->day_number])">
                {{ __('program.actions.start') }}
            </x-ui.button>
        </div>
    @endif
</div>
