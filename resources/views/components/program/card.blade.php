@props(['program'])

{{--
  One program in the catalogue. The whole card is the link, so the touch target
  is the card itself rather than a word inside it (DESIGN.md §11).

  The flex column lives inside the anchor instead of on it: `x-ui.card` already
  sets `block`, and stacking a second display utility on the same element would
  let stylesheet order — not this file — decide which one wins.
--}}

@php
    $excerpt = \Illuminate\Support\Str::limit((string) $program->description, 120);
@endphp

<x-ui.card :href="route('programs.show', $program)" wire:navigate {{ $attributes }}>
    <div class="flex h-full flex-col gap-3">
        <h3 class="line-clamp-2 text-xl font-semibold leading-snug text-ink-50">
            {{ $program->name }}
        </h3>

        <x-program.tags :program="$program"/>

        @if (filled($excerpt))
            <p class="line-clamp-3 text-sm leading-relaxed text-ink-300">{{ $excerpt }}</p>
        @endif

        <x-ui.metric
            size="sm"
            class="mt-auto pt-1"
            :value="$program->days_count"
            :caption="__('program.days.label')"/>
    </div>
</x-ui.card>
