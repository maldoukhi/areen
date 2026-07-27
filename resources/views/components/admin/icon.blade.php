@props(['name'])

{{--
  The panel needs glyphs the public site never asked for — a drag handle, a
  trash can, a copy control. Rather than fork <x-ui.icon>, this component
  forwards every shared name to it and only draws the ones it adds.

  Same contract as the shared icon: Lucide, 1.5 stroke, currentColor, size from
  the caller (DESIGN.md §6).
--}}

@php
    $shared = ['home', 'programs', 'workout', 'account', 'phone', 'whatsapp', 'instagram', 'map', 'globe', 'wifi-off'];

    $sized = \Illuminate\Support\Str::contains((string) $attributes->get('class', ''), ['size-', 'h-', 'w-']);
@endphp

@if (in_array($name, $shared, true))
    <x-ui.icon :name="$name" {{ $attributes }}/>
@else
    <svg {{ $attributes->class($sized ? '' : 'size-5') }}
         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
         stroke-linecap="round" stroke-linejoin="round"
         aria-hidden="true" focusable="false">
        @switch($name)
            @case('menu')
                <path d="M4 6h16"/>
                <path d="M4 12h16"/>
                <path d="M4 18h16"/>
                @break

            @case('close')
                <path d="M18 6 6 18"/>
                <path d="m6 6 12 12"/>
                @break

            @case('dashboard')
                <rect width="7" height="9" x="3" y="3" rx="1"/>
                <rect width="7" height="5" x="14" y="3" rx="1"/>
                <rect width="7" height="9" x="14" y="12" rx="1"/>
                <rect width="7" height="5" x="3" y="16" rx="1"/>
                @break

            @case('muscles')
                <path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"/>
                <path d="m6.08 9.5-3.5 1.6a1 1 0 0 0 0 1.81l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9a1 1 0 0 0 0-1.83l-3.5-1.59"/>
                <path d="m6.08 14.5-3.5 1.6a1 1 0 0 0 0 1.81l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9a1 1 0 0 0 0-1.83l-3.5-1.59"/>
                @break

            @case('users')
                <path d="M18 21a8 8 0 0 0-16 0"/>
                <circle cx="10" cy="8" r="5"/>
                <path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3"/>
                @break

            @case('settings')
                <path d="M20 7h-9"/>
                <path d="M14 17H5"/>
                <circle cx="17" cy="17" r="3"/>
                <circle cx="7" cy="7" r="3"/>
                @break

            @case('plus')
                <path d="M5 12h14"/>
                <path d="M12 5v14"/>
                @break

            @case('trash')
                <path d="M3 6h18"/>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                @break

            @case('grip')
                <circle cx="9" cy="5" r="1"/>
                <circle cx="9" cy="12" r="1"/>
                <circle cx="9" cy="19" r="1"/>
                <circle cx="15" cy="5" r="1"/>
                <circle cx="15" cy="12" r="1"/>
                <circle cx="15" cy="19" r="1"/>
                @break

            {{--
              The keyboard's half of a sortable list. `grip` above is a pointer
              affordance and nothing more, so these two carry the same move for
              anyone who cannot press and drag. See <x-admin.reorder-keys>.
            --}}
            @case('chevron-up')
                <path d="m18 15-6-6-6 6"/>
                @break

            @case('chevron-down')
                <path d="m6 9 6 6 6-6"/>
                @break

            @case('copy')
                <rect width="14" height="14" x="8" y="8" rx="2"/>
                <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
                @break

            @case('check')
                <path d="M20 6 9 17l-5-5"/>
                @break

            @case('search')
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.3-4.3"/>
                @break

            @case('power')
                <path d="M12 2v10"/>
                <path d="M18.4 6.6a9 9 0 1 1-12.8 0"/>
                @break

            @case('image')
                <rect width="18" height="18" x="3" y="3" rx="2"/>
                <circle cx="9" cy="9" r="2"/>
                <path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/>
                @break

            @case('link')
                <path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/>
                <path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/>
                @break

            @case('refresh')
                <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.7 2.7L21 8"/>
                <path d="M21 3v5h-5"/>
                <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.7-2.7L3 16"/>
                <path d="M8 16H3v5"/>
                @break

            @case('pencil')
                <path d="M21.2 6.8a1 1 0 0 0-4-4L3.8 16.2a2 2 0 0 0-.5.8l-1.3 4.4a.5.5 0 0 0 .6.6l4.4-1.3a2 2 0 0 0 .8-.5z"/>
                <path d="m15 5 4 4"/>
                @break

            @case('calendar')
                <path d="M8 2v4"/>
                <path d="M16 2v4"/>
                <rect width="18" height="18" x="3" y="4" rx="2"/>
                <path d="M3 10h18"/>
                @break

            @case('eye')
                <path d="M2 12a10.7 10.7 0 0 1 20 0 10.7 10.7 0 0 1-20 0"/>
                <circle cx="12" cy="12" r="3"/>
                @break

            @case('alert')
                <path d="m21.7 18-8-14a2 2 0 0 0-3.5 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3"/>
                <path d="M12 9v4"/>
                <path d="M12 17h.01"/>
                @break
        @endswitch
    </svg>
@endif
