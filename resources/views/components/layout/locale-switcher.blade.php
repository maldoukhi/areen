{{--
  A real form, not a link: the switch is a POST to `locale.switch`, which stores
  the choice in the session and sends the reader back where they were. Works with
  the keyboard, works without JavaScript, and never depends on hover.

  A locale is always named in its own language, never translated, so the labels
  come from `config('areen.locales')` rather than from a translation file.
--}}

@php
    $locales = (array) config('areen.locales', []);
    $current = app()->getLocale();
    $targets = array_diff_key($locales, [$current => null]);
@endphp

@if ($targets)
    <div {{ $attributes->class('flex items-center gap-1') }}>
        @foreach ($targets as $code => $locale)
            <form method="POST" action="{{ route('locale.switch', $code) }}" class="contents">
                @csrf

                <button type="submit"
                        lang="{{ $code }}"
                        dir="{{ $locale['dir'] ?? 'ltr' }}"
                        title="{{ __('common.locale.switch') }}"
                        aria-label="{{ __('common.locale.switch') }}"
                        class="inline-flex min-h-11 min-w-11 items-center justify-center gap-1.5 rounded-sm px-3
                               text-sm font-medium text-ink-200 transition-colors duration-150 ease-out hover:bg-ink-800">
                    <x-ui.icon name="globe" class="size-5 shrink-0 text-ink-300"/>
                    <span>{{ $locale['name'] ?? $code }}</span>
                </button>
            </form>
        @endforeach
    </div>
@endif
