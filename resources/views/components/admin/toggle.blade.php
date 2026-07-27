@props([
    'label' => null,
    'hint' => null,
    'id' => null,
])

{{--
  A checkbox whose whole row is the target, so the 44px floor holds even though
  the box itself is 20px (DESIGN.md §11).
--}}

@php
    $id ??= 'toggle-'.\Illuminate\Support\Str::random(8);
    $hintId = $hint ? $id.'-hint' : null;
@endphp

<div class="flex flex-col gap-1">
    <label for="{{ $id }}"
           class="flex min-h-11 cursor-pointer items-center gap-3 rounded-sm py-1 text-base text-ink-100">
        <input id="{{ $id }}"
               type="checkbox"
               @if ($hintId) aria-describedby="{{ $hintId }}" @endif
               {{ $attributes->except('class')->class('size-5 shrink-0 rounded-sm border border-ink-600 bg-ink-900 text-brand-400 accent-brand-400') }}>

        <span>{{ $label ?? $slot }}</span>
    </label>

    @if ($hint)
        <p id="{{ $hintId }}" class="ps-8 text-xs leading-normal text-ink-400">{{ $hint }}</p>
    @endif
</div>
