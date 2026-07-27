@php
    $locale = app()->getLocale();
    $dir = config("areen.locales.{$locale}.dir", 'rtl');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    {{--
      viewport-fit=cover is what makes env(safe-area-inset-*) resolve to real values.

      Zoom is locked off at the product owner's decision. Note that Safari has
      ignored `user-scalable=no` since iOS 10, so this binds Android only — on
      iPhone what actually stopped the page jumping is the 16px floor on form
      controls in app.css, which removes the reason Safari zoomed in the first
      place. Lighthouse counts this as an accessibility failure.
    --}}
    <meta name="viewport"
          content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
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
      ONE font is preloaded, and the count is the whole point.

      Without a preload the browser only learns it needs this file after it has
      fetched app.css, parsed it and laid the text out, so the Arabic 400 is
      requested at ~1.1 s and lands at ~2.9 s — text paints in a fallback and
      reflows into Plex a second and a half later.

      Measured in headless Chromium at 390×844, throttled to 1.6 Mbit / 150 ms
      RTT, median of 7 cold loads of `/`:

        no preload            FCP/LCP 1140 ms   CLS 0.0173   font ready 2888 ms
        preload 400 only      FCP/LCP 1144 ms   CLS 0.0027   font ready  949 ms
        preload 400 + 700     FCP/LCP 1588 ms   CLS 0.0005   font ready 1380 ms

      So preloading the body weight is free — it is ready before first paint and
      takes the swap out — while adding the 700 puts another 43 KB in front of
      the render-blocking stylesheet and costs 448 ms of FCP and LCP to remove a
      CLS that was already far inside the good band. One file, not two, and not
      eight: the 500/600 weights and the Latin subsets are fetched normally,
      when something on the page actually needs them.
    --}}
    <link rel="preload" href="/fonts/plex-arabic-arabic-400.woff2" as="font" type="font/woff2" crossorigin>

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
