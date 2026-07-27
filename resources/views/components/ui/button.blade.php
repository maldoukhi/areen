@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'button',
    'full' => false,
])

{{--
  DESIGN.md §5. Primary: brand-400 on brand-950 text — never white, never black.
  Secondary: transparent with an ink-600 border. Ghost: transparent, no border.
  Touch target is 44px tall whatever the label, per DESIGN.md §11.
  Hover is decoration only; the focus-visible ring in app.css carries the keyboard.
--}}

@php
    $base = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-sm px-[18px] py-2.5'
        .' text-base font-medium transition-colors duration-150 ease-out'
        .' disabled:pointer-events-none disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50';

    $tones = [
        'primary' => 'bg-brand-400 text-brand-950 hover:bg-brand-500',
        'secondary' => 'border border-ink-600 bg-transparent text-ink-200 hover:bg-ink-800',
        'ghost' => 'bg-transparent text-ink-200 hover:bg-ink-800',
        'danger' => 'bg-danger text-ink-950 hover:bg-danger/90',
    ];

    $classes = [$base, $tones[$variant] ?? $tones['primary'], 'w-full' => (bool) $full];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
