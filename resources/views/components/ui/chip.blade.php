@props([
    'active' => false,
    'href' => null,
    'tag' => 'span',
])

{{--
  DESIGN.md §5: a pill. Resting ink-800/ink-300, active brand-400 on brand-950.
  Renders as a link when `href` is passed, as a button when `tag="button"`, and
  as a plain label otherwise. Only the interactive forms claim the 44px target.
--}}

@php
    $tag = $href ? 'a' : $tag;
    $interactive = in_array($tag, ['a', 'button'], true);

    $classes = [
        'inline-flex shrink-0 items-center justify-center gap-1.5 rounded-full px-4 text-sm font-medium',
        'transition-colors duration-150 ease-out',
        'min-h-11' => $interactive,
        'py-1.5' => ! $interactive,
        'bg-brand-400 text-brand-950' => (bool) $active,
        'bg-ink-800 text-ink-300' => ! $active,
        'hover:bg-ink-700' => $interactive && ! $active,
    ];
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    @if ($tag === 'button') type="button" @endif
    @if ($interactive && $active) aria-current="true" @endif
    {{ $attributes->class($classes) }}>{{ $slot }}</{{ $tag }}>
