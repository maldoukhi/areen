@props(['name'])

{{--
  Lucide line icons, 1.5 stroke, drawn in `currentColor` (DESIGN.md §6).
  Kept in one file so the shell components do not each carry a copy of the paths.
  Size comes from the caller: `size-5` inline with text, `size-6` standing alone.

  The 20px default is only added when the caller has not sized the icon itself.
  Merging both would leave two size utilities on one element and let the stylesheet
  order — not the caller — decide which one wins.
--}}

@php
    $sized = \Illuminate\Support\Str::contains((string) $attributes->get('class', ''), ['size-', 'h-', 'w-']);
@endphp

<svg {{ $attributes->class($sized ? '' : 'size-5') }}
     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
     stroke-linecap="round" stroke-linejoin="round"
     aria-hidden="true" focusable="false">
    @switch($name)
        @case('home')
            <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/>
            <path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            @break

        @case('programs')
            <rect width="8" height="4" x="8" y="2" rx="1"/>
            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
            <path d="M12 11h4"/>
            <path d="M12 16h4"/>
            <path d="M8 11h.01"/>
            <path d="M8 16h.01"/>
            @break

        @case('workout')
            <path d="m6.5 6.5 11 11"/>
            <path d="m21 21-1-1"/>
            <path d="m3 3 1 1"/>
            <path d="m18 22 4-4"/>
            <path d="m2 6 4-4"/>
            <path d="m3 10 7-7"/>
            <path d="m14 21 7-7"/>
            @break

        @case('account')
            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
            @break

        @case('phone')
            <path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"/>
            @break

        @case('whatsapp')
            <path d="M2.992 16.342a2 2 0 0 1 .094 1.167l-1.065 3.29a1 1 0 0 0 1.236 1.168l3.413-.977a2 2 0 0 1 1.099.092 10 10 0 1 0-4.777-4.74"/>
            @break

        @case('instagram')
            <rect width="20" height="20" x="2" y="2" rx="5"/>
            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37"/>
            <path d="M17.5 6.5h.01"/>
            @break

        @case('map')
            <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/>
            <circle cx="12" cy="10" r="3"/>
            @break

        @case('globe')
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/>
            <path d="M2 12h20"/>
            @break

        @case('wifi-off')
            <path d="M12 20h.01"/>
            <path d="M8.5 16.429a5 5 0 0 1 7 0"/>
            <path d="M5 12.859a10 10 0 0 1 5.17-2.69"/>
            <path d="M19 12.859a10 10 0 0 0-2.007-1.523"/>
            <path d="M2 8.82a15 15 0 0 1 4.177-2.643"/>
            <path d="M22 8.82a15 15 0 0 0-11.288-3.764"/>
            <path d="m2 2 20 20"/>
            @break
    @endswitch
</svg>
