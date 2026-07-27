@php
    $locale = app()->getLocale();
    $dir = config("areen.locales.{$locale}.dir", 'rtl');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="{{ config('areen.brand.theme_color') }}">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $title ?? __('auth.login.title') }} · {{ __('common.app_name') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ substr(md5_file(public_path('favicon.png')), 0, 8) }}">

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
