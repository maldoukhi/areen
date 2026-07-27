@props([
    'settings' => null,
    // How the image is sized. A caller that needs different geometry — the hero
    // crest fills its box rather than sitting in a 40px header row — replaces
    // this wholesale instead of fighting a hardcoded cap.
    'size' => 'h-10 w-auto max-w-[104px] object-contain',
    'markClass' => 'size-8 shrink-0 text-brand-400',
])

{{--
  The club's own logo, with the Areen mark standing in when there is none.

  Resolving it is fiddlier than it looks — the logo shipped with an installation
  is a repository asset while an upload from the panel lives on the storage disk,
  and a settings row may not exist at all on a fresh install. Every place that
  shows the club (header, install banner, print header) needs the same answer, so
  the logic lives here once instead of drifting apart in three copies.
--}}

@php
    $settings ??= rescue(
        fn () => class_exists(\App\Models\Setting::class) && \Illuminate\Support\Facades\Schema::hasTable('settings')
            ? \App\Models\Setting::current()
            : null,
        null,
        false,
    );

    $read = fn (string $key) => rescue(fn () => $settings?->{$key}, null, false);

    $logo = $read('logo_url');

    // The accessor is the normal path; this covers a model that predates it.
    if (! $logo && ($path = $read('logo_path'))) {
        $logo = \Illuminate\Support\Str::startsWith($path, ['http://', 'https://', '/'])
            ? $path
            : rescue(fn () => \Illuminate\Support\Facades\Storage::disk('public')->url($path), null, false);
    }
@endphp

@if ($logo)
    <img src="{{ $logo }}" alt="" {{ $attributes->class($size) }}>
@else
    <x-brand.mark {{ $attributes->class($markClass) }}/>
@endif
