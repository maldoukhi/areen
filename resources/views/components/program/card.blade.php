@props([
    'program',
    /*
     | The heading level the card's title is written at — NOT the program's
     | training level, which is `$program->level`. A card nested under a section
     | heading is an h3; a card that is the page's content, directly under the
     | h1, is an h2. A skipped level reads as a missing section to a screen
     | reader, so the page has to be able to say which it is.
     */
    'headingLevel' => 3,
])

{{--
  One program in the catalogue. The whole card is the link, so the touch target
  is the card itself rather than a word inside it (DESIGN.md §11).

  The day count is the card's anchor: it is the first thing anyone compares
  between two plans, so it is set as a large tabular figure instead of being
  buried in a row of grey labels. A tinted rail on the start edge carries the
  level, which gives the grid a rhythm you can scan without reading.

  The flex column lives inside the anchor instead of on it: `x-ui.card` already
  sets `block`, and stacking a second display utility on the same element would
  let stylesheet order — not this file — decide which one wins.
--}}

@php
    $excerpt = \Illuminate\Support\Str::limit((string) $program->description, 110);

    // The rail is the only place colour does this work, so the chips stay neutral.
    $rails = [
        'beginner' => 'bg-brand-400/50',
        'intermediate' => 'bg-brand-300/60',
        'advanced' => 'bg-ember/70',
    ];

    $rail = $rails[$program->level->value] ?? 'bg-brand-400/50';
@endphp

<x-ui.card :href="route('programs.show', $program)" wire:navigate
           {{ $attributes->class('lift relative overflow-hidden p-0!') }}>
    {{-- On the start edge, so it flips with the reading direction. --}}
    <span class="absolute inset-y-0 start-0 w-1 {{ $rail }}"></span>

    <div class="flex h-full flex-col gap-3 p-5 ps-6">
        <div class="flex items-start justify-between gap-4">
            <h{{ $headingLevel }} class="line-clamp-2 text-xl font-semibold leading-snug text-ink-50">
                {{ $program->name }}
            </h{{ $headingLevel }}>

            <span class="shrink-0 text-end leading-none">
                <span class="tabular block text-[2rem] font-bold leading-none text-brand-400">{{ $program->days_count }}</span>
                <span class="mt-1 block text-[0.6875rem] font-medium text-ink-300">{{ __('program.days.label') }}</span>
            </span>
        </div>

        <x-program.tags :program="$program"/>

        @if (filled($excerpt))
            <p class="line-clamp-2 text-sm leading-relaxed text-ink-300">{{ $excerpt }}</p>
        @endif
    </div>
</x-ui.card>
