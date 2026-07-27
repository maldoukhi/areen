@props([
    'value',
    'label' => null,
    'hint' => null,
])

{{--
  A shareable string plus the one control that matters: copy it.

  The field stays a real, readable, selectable input so the value can still be
  copied by hand where the Clipboard API is unavailable — it needs a secure
  context, and a club on a plain http address inside the gym would otherwise be
  left with a dead button. `execCommand` is the fallback for exactly that case.
--}}

@php
    $id = 'copy-'.\Illuminate\Support\Str::random(8);
@endphp

<div {{ $attributes->class('flex flex-col gap-2') }}
     x-data="{
         copied: false,
         copy() {
             const field = this.$refs.value;
             field.select();
             field.setSelectionRange(0, field.value.length);

             const done = () => { this.copied = true; setTimeout(() => this.copied = false, 2000) };

             if (navigator.clipboard?.writeText) {
                 navigator.clipboard.writeText(field.value).then(done, () => { document.execCommand('copy'); done() });
             } else {
                 document.execCommand('copy');
                 done();
             }
         },
     }">

    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-ink-200">{{ $label }}</label>
    @endif

    <div class="flex gap-2">
        <input id="{{ $id }}"
               x-ref="value"
               type="text"
               readonly
               dir="ltr"
               value="{{ $value }}"
               class="min-h-11 min-w-0 flex-1 rounded-sm border border-ink-700 bg-ink-900 px-3 py-2.5
                      text-start text-sm text-ink-100 focus:border-brand-400">

        <button type="button"
                x-on:click="copy()"
                class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-sm border border-ink-600
                       px-4 text-sm font-medium text-ink-200 transition-colors duration-150 ease-out hover:bg-ink-800">
            <span class="contents" x-show="! copied">
                <x-admin.icon name="copy" class="size-5"/>
                <span>{{ __('common.actions.copy') }}</span>
            </span>

            <span class="contents text-success" x-show="copied" style="display: none">
                <x-admin.icon name="check" class="size-5"/>
                <span>{{ __('common.actions.copied') }}</span>
            </span>
        </button>
    </div>

    @if ($hint)
        <p class="text-xs leading-normal text-ink-400">{{ $hint }}</p>
    @endif
</div>
