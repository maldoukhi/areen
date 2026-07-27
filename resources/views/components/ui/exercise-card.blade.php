@props(['exercise', 'showMuscle' => true])

{{--
  One exercise in a listing grid. Shared by /exercises and /muscles/{slug} so the
  two pages cannot drift apart — the muscle page is a filtered library, not a
  second design.

  DESIGN.md §5 card: ink-800 on an ink-700 hairline, radius-lg, padding 20.
  Metadata is typographic rather than chips: a chip is ink-800 (§5) and would
  vanish on an ink-800 card, and §1 rules decoration out anyway.

  Every seeded exercise has a null `media_path` and a null `youtube_url`, so the
  media block is the exception and its absence is the normal, finished state —
  never an empty frame, never a broken <img>.
--}}

@php
    $mediaPath = $exercise->media_path;

    $media = filled($mediaPath)
        ? (\Illuminate\Support\Str::startsWith($mediaPath, ['http://', 'https://', '/'])
            ? $mediaPath
            : rescue(fn () => \Illuminate\Support\Facades\Storage::disk('public')->url($mediaPath), null, false))
        : null;

    $media ??= $exercise->youtube_thumbnail_url;

    // `equipment` is nullable and stores a stable English slug that the UI
    // translates (CLAUDE.md §4). A row without one simply drops the word.
    $equipment = filled($exercise->equipment)
        ? __('exercise.equipment.'.$exercise->equipment)
        : null;

    $meta = array_filter([$equipment, $exercise->difficulty?->label()]);
@endphp

<x-ui.card :href="route('exercises.show', $exercise)"
           wire:navigate
           {{ $attributes->class('flex flex-col gap-3') }}>
    @if ($media)
        {{-- DESIGN.md §6: 4:3, ink-800 bed, radius-md, lazy. --}}
        <img src="{{ $media }}"
             alt=""
             loading="lazy"
             decoding="async"
             x-data
             x-on:error="$el.remove()"
             class="aspect-4/3 w-full rounded-md bg-ink-900 object-cover">
    @endif

    <h3 class="text-lg font-semibold leading-snug text-ink-50">{{ $exercise->name }}</h3>

    {{-- On /muscles/{slug} every card names the same group, so the page turns
         this line off rather than repeating itself down the grid. --}}
    @if ($showMuscle)
        <p class="text-sm leading-normal text-ink-300">{{ $exercise->muscleGroup->name }}</p>
    @endif

    @if ($meta !== [])
        <p class="mt-auto text-xs leading-normal text-ink-400">{{ implode(' · ', $meta) }}</p>
    @endif
</x-ui.card>
