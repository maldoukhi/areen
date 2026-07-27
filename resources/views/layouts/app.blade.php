@php
    $locale = app()->getLocale();
    $dir = config("areen.locales.{$locale}.dir", 'rtl');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    {{-- viewport-fit=cover is what makes env(safe-area-inset-*) resolve to real values. --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ config('areen.brand.theme_color') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ __('common.app_name') }}">

    <title>@yield('title', __('common.app_name'))</title>

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/brand/apple-touch-icon.png">
    <link rel="manifest" href="{{ route('manifest') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-dvh bg-ink-950 text-ink-100 antialiased">
    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:start-3 focus:z-50
              focus:rounded-sm focus:bg-brand-400 focus:px-4 focus:py-2 focus:text-brand-950">
        {{ __('common.nav.home') }}
    </a>

    <main id="main" class="mx-auto w-full max-w-[1200px]">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    @livewireScripts
</body>
</html>
