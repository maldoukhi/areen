@props(['href' => null])

{{--
  DESIGN.md §5: ink-800 surface, ink-700 hairline, radius-lg (16px), padding 20px.
  Elevation comes from the surface, never from a shadow.
--}}

@php
    // The brand-400/40 hover border is a hint that the card can be opened, so it
    // is only offered when the card actually goes somewhere or handles a click.
    $interactive = $href
        || $attributes->hasAny(['wire:click', 'x-on:click', '@click', 'onclick', 'role']);

    $classes = [
        'block rounded-lg border border-ink-700 bg-ink-800 p-5 transition-colors duration-150 ease-out',
        'hover:border-brand-400/40 focus-visible:border-brand-400' => (bool) $interactive,
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <div {{ $attributes->class($classes) }}>{{ $slot }}</div>
@endif
