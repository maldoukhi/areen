@props(['clubName' => null])

{{--
  The club's name in the rail, never the club's logo: DESIGN.md §7 gives the
  logo a 40px floor and a quarter of its height in clear space, which a 64px
  rail head cannot honour. The Areen mark carries the platform instead.
--}}

<div {{ $attributes->class('flex items-center gap-3 px-4 py-4') }}>
    <x-brand.mark class="size-8 shrink-0 text-brand-400"/>

    <div class="min-w-0">
        <p class="truncate text-base font-semibold leading-tight text-ink-50">{{ $clubName ?? __('common.app_name') }}</p>
        <p class="truncate text-xs font-medium leading-normal text-ink-400">{{ __('admin.title') }}</p>
    </div>
</div>
