{{--
  DESIGN.md §11: the installed app gets a bottom bar — 60px plus the gesture
  area, ink-900 on an ink-800 top border, four destinations, 22px icon over 11px
  label, brand-400 when active and ink-400 when not. The browser never sees it,
  which is the mirror of the footer rule.

  The primary action belongs within thumb reach, so this bar is the one piece of
  chrome that lives at the bottom of the screen.

  Most of these routes land in later phases. Each item names the candidates it
  would answer to and falls back to `#` until one of them is registered, so the
  shell never throws while the app is half built.
--}}

@php
    $resolve = function (array $names): string {
        foreach ($names as $name) {
            if (\Illuminate\Support\Facades\Route::has($name)) {
                return route($name);
            }
        }

        return '#';
    };

    $items = [
        [
            'icon' => 'home',
            'label' => __('common.nav.home'),
            'href' => $resolve(['home']),
            'active' => request()->routeIs('home'),
        ],
        [
            'icon' => 'programs',
            'label' => __('common.nav.programs'),
            'href' => $resolve(['programs.index', 'programs']),
            'active' => request()->routeIs('programs*'),
        ],
        [
            'icon' => 'workout',
            'label' => __('common.nav.my_workout'),
            'href' => $resolve(['dashboard', 'dashboard.index']),
            'active' => request()->routeIs('dashboard*'),
        ],
        [
            'icon' => 'account',
            'label' => __('common.nav.account'),
            'href' => $resolve(['account', 'profile.edit', 'login']),
            'active' => request()->routeIs('account*') || request()->routeIs('profile*') || request()->routeIs('login'),
        ],
    ];
@endphp

<nav aria-label="{{ __('common.a11y.primary_nav') }}"
     {{ $attributes->class('fixed inset-x-0 bottom-0 z-50 hidden border-t border-ink-800 bg-ink-900 safe-pb [@media(display-mode:standalone)]:block print:hidden') }}>
    <ul class="mx-auto flex w-full max-w-[520px] items-stretch">
        @foreach ($items as $item)
            <li class="flex-1">
                <a href="{{ $item['href'] }}"
                   @if ($item['active']) aria-current="page" @endif
                   @if ($item['href'] === '#') aria-disabled="true" @endif
                   @class([
                       'flex h-[60px] flex-col items-center justify-center gap-1 rounded-sm text-center',
                       'transition-colors duration-150 ease-out',
                       'text-brand-400' => $item['active'],
                       'text-ink-300' => ! $item['active'],
                   ])>
                    <x-ui.icon :name="$item['icon']" class="size-[22px] shrink-0"/>
                    <span class="text-[11px] font-medium leading-none">{{ $item['label'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
