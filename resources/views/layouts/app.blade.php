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

    {{--
      Resolved into a variable rather than yielded straight into the tag, so the
      social card can be given the exact same string. `og:title` disagreeing with
      the tab title is the classic way a share preview goes stale.

      Both title styles are covered: `@section('title', …)` from the Blade pages
      and Livewire's own `$view->title(…)`, which arrives as `$title`.
    --}}
    @php
        $documentTitle = trim($__env->yieldContent('title')) ?: ($title ?? __('common.app_name'));
    @endphp

    <title>{{ $documentTitle }}</title>

    <link rel="icon" href="/favicon.png" type="image/png">
    <link rel="apple-touch-icon" href="/brand/apple-touch-icon.png">
    <link rel="manifest" href="{{ route('manifest') }}">

    {{--
      The two weights that draw the first screen — the 400 the body is set in and
      the 700 every page's h1 uses — in the Arabic subset, which is what the
      default locale actually renders. They are discovered late otherwise: the
      browser has to fetch app.css, parse it, lay out the text and only then
      learn which @font-face it needs, which is a second round trip in front of
      the first paint on a gym connection.

      Only two of the eight files are preloaded on purpose. Preloading all of
      them would put ~257 KB of fonts ahead of the stylesheet and make the page
      slower, not faster; the 500/600 weights and the Latin subsets are still
      fetched normally, when something on the page needs them.
    --}}
    <link rel="preload" href="/fonts/plex-arabic-arabic-400.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/plex-arabic-arabic-700.woff2" as="font" type="font/woff2" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    {{--
      Pages push `<x-seo.page>` here to describe themselves; Blade renders a
      pushed block immediately, so by the time the stack is emitted the
      declaration is already on record and `<x-seo.meta>` below can read it.
    --}}
    @stack('head')

    <x-seo.meta :title="$documentTitle"/>
</head>
<body class="flex min-h-dvh flex-col bg-ink-950 text-ink-100 antialiased">
    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:start-3 focus:z-[60]
              focus:inline-flex focus:min-h-11 focus:items-center focus:rounded-sm focus:bg-brand-400
              focus:px-4 focus:py-2 focus:font-medium focus:text-brand-950">
        {{ __('common.actions.skip_to_content') }}
    </a>

    {{--
      The offline strip and the header travel together so they stick to the top
      as one block, and so the strip can hand the notch padding back and forth
      with the header instead of both claiming it at once.
    --}}
    <div class="sticky top-0 z-50">
        <x-layout.offline-bar/>
        <x-layout.header/>
    </div>

    {{--
      Both calling styles are supported: `@extends('layouts.app')` with a
      `content` section (the plain Blade pages), and the slot form Livewire uses
      when it renders a page component into `layouts::app`. Only one of the two
      ever has anything in it.

      In standalone mode the bottom nav floats over the page, so the main column
      reserves its height plus the gesture area.
    --}}
    <main id="main"
          tabindex="-1"
          class="mx-auto w-full max-w-[1200px] flex-1 focus:outline-none
                 [@media(display-mode:standalone)]:pb-[calc(60px+16px+env(safe-area-inset-bottom))]">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <x-layout.footer/>
    <x-layout.bottom-nav/>

    {{--
      Both start hidden and stay that way unless their own conditions are met:
      the update bar only after the service worker reports a replacement waiting,
      the install banner only outside the installed app and only until it is
      dismissed. The update bar wins if they ever collide.

      The rest timer and the wake-lock switch are deliberately *not* here — they
      belong to the day page, which drops in `<x-pwa.rest-timer/>` and
      `<x-pwa.wake-lock-toggle/>` where they make sense.
    --}}
    <x-pwa.update-bar/>
    <x-pwa.install-banner/>

    @livewireScripts
    @stack('scripts')
</body>
</html>
