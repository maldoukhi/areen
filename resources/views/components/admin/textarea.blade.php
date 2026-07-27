@props(['error' => false, 'rows' => 3])

{{-- Matches <x-ui.field>'s control skin; only the height differs. --}}

@php
    $control = 'block w-full rounded-sm border bg-ink-900 px-3 py-2.5 text-base leading-relaxed text-ink-100'
        .' transition-colors duration-150 ease-out placeholder:text-ink-500'
        .($error ? ' border-danger' : ' border-ink-700 focus:border-brand-400');
@endphp

<textarea rows="{{ $rows }}" {{ $attributes->class($control) }}>{{ $slot }}</textarea>
