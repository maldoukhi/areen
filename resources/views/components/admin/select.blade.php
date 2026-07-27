@props(['error' => false])

{{--
  The same control skin <x-ui.field> gives its input — ink-900 on an ink-700
  hairline, brand-400 on focus, 44px tall — so a select never reads as a
  different species of field. Pass the <option> list through the slot.
--}}

@php
    $control = 'block w-full min-h-11 appearance-none rounded-sm border bg-ink-900 px-3 py-2.5 text-base text-ink-100'
        .' transition-colors duration-150 ease-out'
        .($error ? ' border-danger' : ' border-ink-700 focus:border-brand-400');
@endphp

<select {{ $attributes->class($control) }}>{{ $slot }}</select>
