@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'type' => 'text',
    'hint' => null,
    'error' => null,
    'required' => false,
])

{{--
  DESIGN.md §5: ink-900 field on an ink-700 hairline; focus turns the border
  brand-400 and app.css adds the 3px brand-400/40 ring. 44px minimum height.
  Pass a slot to swap the control (select, textarea, a Livewire input) while
  keeping the label, hint and error wiring.
--}}

@php
    $id ??= $name ? 'field-'.\Illuminate\Support\Str::slug((string) $name) : 'field-'.\Illuminate\Support\Str::random(8);
    $hintId = $hint ? $id.'-hint' : null;
    $errorId = $error ? $id.'-error' : null;
    $describedBy = trim(implode(' ', array_filter([$hintId, $errorId]))) ?: null;

    $control = 'block w-full min-h-11 rounded-sm border bg-ink-900 px-3 py-2.5 text-base text-ink-100'
        .' transition-colors duration-150 ease-out placeholder:text-ink-500'
        .($error ? ' border-danger' : ' border-ink-700 focus:border-brand-400');
@endphp

<div {{ $attributes->only('class')->class('flex flex-col gap-2') }}>
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-ink-200">
            {{ $label }}

            @if ($required)
                <span class="text-danger" aria-hidden="true">*</span>
                <span class="sr-only">{{ __('common.fields.required') }}</span>
            @endif
        </label>
    @endif

    @if ($slot->isNotEmpty())
        {{ $slot }}
    @else
        <input id="{{ $id }}"
               type="{{ $type }}"
               @if ($name) name="{{ $name }}" @endif
               @if ($required) required aria-required="true" @endif
               @if ($error) aria-invalid="true" @endif
               @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
               {{ $attributes->except('class')->class($control) }}>
    @endif

    @if ($hint)
        <p id="{{ $hintId }}" class="text-xs leading-normal text-ink-400">{{ $hint }}</p>
    @endif

    @if ($error)
        <p id="{{ $errorId }}" class="text-sm leading-normal text-danger">{{ $error }}</p>
    @endif
</div>
