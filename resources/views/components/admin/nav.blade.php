@props(['onNavigate' => null])

{{--
  One list, rendered twice: pinned to the side on a desk, inside the drawer on a
  phone. Which sections exist is a permission question, so it is asked of the
  policies through @can rather than of the user's role in the markup.

  `onNavigate` lets the drawer close itself when a link is taken.
--}}

@php
    $sections = [
        ['route' => 'admin.dashboard', 'pattern' => 'admin.dashboard', 'icon' => 'dashboard', 'label' => __('admin.dashboard'), 'ability' => null],
        ['route' => 'admin.programs.index', 'pattern' => 'admin.programs.*', 'icon' => 'programs', 'label' => __('admin.entities.programs'), 'ability' => ['viewAny', \App\Models\Program::class]],
        ['route' => 'admin.exercises.index', 'pattern' => 'admin.exercises.*', 'icon' => 'workout', 'label' => __('admin.entities.exercises'), 'ability' => ['viewAny', \App\Models\Exercise::class]],
        ['route' => 'admin.muscle-groups.index', 'pattern' => 'admin.muscle-groups.*', 'icon' => 'muscles', 'label' => __('admin.entities.muscle_groups'), 'ability' => ['viewAny', \App\Models\MuscleGroup::class]],
        ['route' => 'admin.trainees.index', 'pattern' => 'admin.trainees.*', 'icon' => 'users', 'label' => __('admin.entities.trainees'), 'ability' => ['viewAny', \App\Models\User::class]],
        ['route' => 'admin.settings.edit', 'pattern' => 'admin.settings.*', 'icon' => 'settings', 'label' => __('admin.entities.settings'), 'ability' => ['viewAny', \App\Models\Setting::class]],
    ];
@endphp

<nav {{ $attributes->class('flex flex-col gap-1 p-3') }} aria-label="{{ __('admin.shell.sections') }}">
    @foreach ($sections as $section)
        @if ($section['ability'] === null || auth()->user()?->can(...$section['ability']))
            @php $active = request()->routeIs($section['pattern']); @endphp

            <a href="{{ route($section['route']) }}"
               wire:navigate
               @if ($onNavigate) x-on:click="{{ $onNavigate }}" @endif
               @if ($active) aria-current="page" @endif
               wire:key="admin-nav-{{ $section['route'] }}"
               @class([
                   'flex min-h-11 items-center gap-3 rounded-sm px-3 text-start text-base font-medium transition-colors duration-150 ease-out',
                   'bg-brand-400 text-brand-950' => $active,
                   'text-ink-200 hover:bg-ink-800' => ! $active,
               ])>
                <x-admin.icon :name="$section['icon']" class="size-5 shrink-0"/>
                <span class="truncate">{{ $section['label'] }}</span>
            </a>
        @endif
    @endforeach
</nav>
