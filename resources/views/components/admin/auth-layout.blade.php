@php
    $locale = app()->getLocale();
    $dir = config("areen.locales.{$locale}.dir", 'rtl');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ config('areen.brand.theme_color') }}">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $title ?? __('auth.login.title') }} · {{ __('common.app_name') }}</title>

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
{{--
  One screen, one goal (DESIGN.md §1). No navigation, no footer, nothing to
  wander off into — the only thing here is the door.
--}}
<body class="bg-ink-950 text-ink-100 antialiased">
    <div class="mx-auto flex min-h-dvh w-full max-w-md flex-col px-4">
        {{ $slot }}
    </div>

    @livewireScripts
</body>
</html>
