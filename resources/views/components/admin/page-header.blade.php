@props([
    'title' => null,
    'description' => null,
    'back' => null,
    'backLabel' => null,
])

{{--
  The section heading inside the page, under the shell's own title bar. One
  clear goal per screen (DESIGN.md §12), so `actions` is meant to hold a single
  primary control — anything else belongs in the row it acts on.
--}}

<div {{ $attributes->class('mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between') }}>
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}"
               wire:navigate
               class="-ms-2 mb-1 inline-flex min-h-11 items-center rounded-sm px-2 text-sm font-medium text-ink-300 hover:bg-ink-800">
                {{ $backLabel ?? __('admin.actions.back_to_list') }}
            </a>
        @endif

        @if ($title)
            <h2 class="text-2xl font-semibold text-ink-50">{{ $title }}</h2>
        @endif

        @if ($description)
            <p class="mt-1 max-w-[65ch] text-sm leading-relaxed text-ink-300">{{ $description }}</p>
        @endif

        {{ $slot }}
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
