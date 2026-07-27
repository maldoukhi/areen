@props(['settings' => null])

{{--
  DESIGN.md §11: 56px bar that stays put while the page scrolls, ink-950/90 with a
  backdrop blur, clearing the notch through `safe-pt`.

  Club identity is never written into the code — it comes from the `settings` row.
  That table and its model belong to another slice of the app and may not exist
  yet, so every read is guarded and the Areen mark stands in when there is no
  club logo. Pass `:settings` explicitly to skip the lookup.
--}}

@php
    $settings ??= rescue(
        fn () => class_exists(\App\Models\Setting::class) && \Illuminate\Support\Facades\Schema::hasTable('settings')
            ? \App\Models\Setting::current()
            : null,
        null,
        false,
    );

    // Reads stay wrapped: the schema is still moving and a missing column or
    // accessor must never take the whole shell down.
    $value = fn (string $key) => rescue(fn () => $settings?->{$key}, null, false);

    $clubName = $value('club_name_'.app()->getLocale())
        ?: $value('club_name_ar')
        ?: $value('club_name')
        ?: __('common.app_name');

    $logo = $value('logo_url');

    if (! $logo && ($logoPath = $value('logo_path'))) {
        $logo = \Illuminate\Support\Str::startsWith($logoPath, ['http://', 'https://', '/'])
            ? $logoPath
            : rescue(fn () => \Illuminate\Support\Facades\Storage::disk('public')->url($logoPath), null, false);
    }
@endphp

<header {{ $attributes->class([
    'sticky top-0 z-40 border-b border-ink-800 bg-ink-950/90 backdrop-blur-md',
    // When the offline strip above is showing it already owns the notch, so the
    // header drops its own safe padding instead of stacking a second one.
    'safe-pt peer-data-[offline=true]:pt-0!',
    'print:hidden',
]) }}>
    <div class="mx-auto flex h-14 w-full max-w-[1200px] items-center justify-between gap-3 px-4">
        <a href="{{ route('home') }}"
           class="-ms-2 inline-flex min-h-11 items-center gap-3 rounded-sm px-2 text-start"
           aria-label="{{ __('common.a11y.club_home') }}">
            @if ($logo)
                <img src="{{ $logo }}" alt="" class="h-10 w-auto max-w-[104px] object-contain" width="104" height="40">
            @else
                <x-brand.mark class="size-8 shrink-0 text-brand-400"/>
            @endif

            <span class="truncate text-base font-semibold leading-tight text-ink-50">{{ $clubName }}</span>
        </a>

        <x-layout.locale-switcher/>
    </div>
</header>
