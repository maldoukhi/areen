@php
    $locale = app()->getLocale();
    $dir = config("areen.locales.{$locale}.dir", 'rtl');

    $settings = rescue(fn () => \App\Models\Setting::current(), null, false);
    $clubName = rescue(fn () => $settings?->club_name, null, false) ?: __('common.app_name');

    $user = auth()->user();

    /*
     | The bar title is derived from the route rather than pushed up from each
     | page: a page component's data never reaches its layout, and a title that
     | has to be passed by hand is a title that goes stale.
     */
    $titles = [
        'admin.dashboard' => __('admin.dashboard'),
        'admin.programs.index' => __('admin.entities.programs'),
        'admin.programs.create' => __('admin.entities.program'),
        'admin.programs.edit' => __('admin.entities.program'),
        'admin.programs.day' => __('admin.day.builder'),
        'admin.exercises.index' => __('admin.entities.exercises'),
        'admin.exercises.create' => __('admin.entities.exercise'),
        'admin.exercises.edit' => __('admin.entities.exercise'),
        'admin.muscle-groups.index' => __('admin.entities.muscle_groups'),
        'admin.trainees.index' => __('admin.entities.trainees'),
        'admin.trainees.show' => __('admin.entities.trainee'),
        'admin.settings.edit' => __('admin.settings.title'),
    ];

    $pageTitle = $titles[request()->route()?->getName()] ?? __('admin.title');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="{{ config('areen.brand.theme_color') }}">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $pageTitle }} · {{ __('admin.title') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ substr(md5_file(public_path('favicon.png')), 0, 8) }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-ink-950 text-ink-100 antialiased">
    <a href="#admin-main"
       class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:start-3 focus:z-[60]
              focus:inline-flex focus:min-h-11 focus:items-center focus:rounded-sm focus:bg-brand-400
              focus:px-4 focus:py-2 focus:font-medium focus:text-brand-950">
        {{ __('common.actions.skip_to_content') }}
    </a>

    <div x-data="{ drawer: false }"
         x-on:keydown.escape.window="drawer = false"
         class="flex min-h-dvh">

        {{-- Pinned rail from md up. DESIGN.md §2: ink-900 surface, ink-800 hairline. --}}
        <aside class="hidden w-64 shrink-0 flex-col border-e border-ink-800 bg-ink-900 md:flex">
            <x-admin.brand :club-name="$clubName"/>
            <x-admin.nav class="flex-1 overflow-y-auto"/>
            <x-admin.account :user="$user"/>
        </aside>

        {{--
          The same rail as a drawer on a phone. `style="display:none"` is the
          pre-Alpine state so the panel never flashes open on a slow connection.
        --}}
        <div x-show="drawer" style="display: none" class="fixed inset-0 z-50 md:hidden">
            <button type="button"
                    x-on:click="drawer = false"
                    tabindex="-1"
                    aria-label="{{ __('admin.shell.close_menu') }}"
                    class="absolute inset-0 h-full w-full bg-ink-950/80"></button>

            <div class="relative flex h-full w-72 max-w-[85%] flex-col border-e border-ink-800 bg-ink-900 safe-pt"
                 role="dialog"
                 aria-modal="true"
                 aria-label="{{ __('admin.shell.menu') }}">
                <div class="flex items-start justify-between gap-2 pe-2">
                    <x-admin.brand :club-name="$clubName"/>

                    <button type="button"
                            x-on:click="drawer = false"
                            aria-label="{{ __('admin.shell.close_menu') }}"
                            class="mt-3 inline-flex size-11 shrink-0 items-center justify-center rounded-sm text-ink-300 hover:bg-ink-800">
                        <x-admin.icon name="close" class="size-5"/>
                    </button>
                </div>

                <x-admin.nav class="flex-1 overflow-y-auto" on-navigate="drawer = false"/>
                <x-admin.account :user="$user"/>
            </div>
        </div>

        <div class="flex min-w-0 flex-1 flex-col">
            {{-- DESIGN.md §11: 56px bar, stays put, ink-950/90 with a blur. --}}
            <header class="sticky top-0 z-40 border-b border-ink-800 bg-ink-950/90 backdrop-blur-md safe-pt">
                <div class="flex h-14 items-center gap-2 px-4">
                    <button type="button"
                            x-on:click="drawer = true"
                            aria-label="{{ __('admin.shell.open_menu') }}"
                            class="-ms-2 inline-flex size-11 shrink-0 items-center justify-center rounded-sm text-ink-200 hover:bg-ink-800 md:hidden">
                        <x-admin.icon name="menu" class="size-6"/>
                    </button>

                    <h1 class="min-w-0 flex-1 truncate text-lg font-semibold text-ink-50">{{ $pageTitle }}</h1>

                    <a href="{{ url('/') }}"
                       class="inline-flex min-h-11 items-center gap-2 rounded-sm px-3 text-sm font-medium text-ink-300 hover:bg-ink-800">
                        <x-admin.icon name="eye" class="size-5 shrink-0"/>
                        <span class="hidden sm:inline">{{ __('admin.shell.public_site') }}</span>
                    </a>
                </div>
            </header>

            <main id="admin-main"
                  tabindex="-1"
                  class="mx-auto w-full max-w-[1200px] flex-1 px-4 pt-4 pb-16 focus:outline-none safe-pb">
                <x-admin.flash/>

                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
