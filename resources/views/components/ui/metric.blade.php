@props([
    'value' => null,
    'caption' => null,
    'unit' => null,
    'tone' => 'brand',
    'size' => 'md',
])

{{--
  The number is the hero (DESIGN.md §1). Always tabular so digits do not dance
  when a value updates, always Western digits, always a caption underneath.

  size: sm = 20px — the sets×reps figure on an exercise row (DESIGN.md §5)
        md = 1.5rem · lg = 2rem · xl = 2.5rem — the Metric role in DESIGN.md §3
  tone: ember is capped at one appearance per screen (DESIGN.md §2).
--}}

@php
    $sizes = [
        'sm' => 'text-xl',
        'md' => 'text-2xl',
        'lg' => 'text-[2rem]',
        'xl' => 'text-[2.5rem]',
    ];

    $tones = [
        'brand' => 'text-brand-400',
        'ember' => 'text-ember',
        'success' => 'text-success',
        'danger' => 'text-danger',
        'ink' => 'text-ink-100',
    ];
@endphp

<div {{ $attributes->class('flex flex-col gap-1') }}>
    <span class="tabular font-bold leading-none {{ $sizes[$size] ?? $sizes['md'] }} {{ $tones[$tone] ?? $tones['brand'] }}">
        {{ $value ?? $slot }}@if ($unit)<span class="ms-1 text-sm font-medium text-ink-400">{{ $unit }}</span>@endif
    </span>

    @if ($caption)
        <span class="text-xs font-medium leading-normal text-ink-400">{{ $caption }}</span>
    @endif
</div>
