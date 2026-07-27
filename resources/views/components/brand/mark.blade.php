{{--
  Areen platform mark — an open arch (the den) with three bars rising right-to-left.
  Draws in `currentColor`, so it takes the colour of whatever surface it sits on.
--}}
@php
    // Merging a default size would emit two size utilities and leave the winner
    // to stylesheet order rather than to the caller. Only size it when asked not to.
    $sized = str_contains($attributes->get('class', ''), 'size-')
        || str_contains($attributes->get('class', ''), 'h-');
@endphp

<svg {{ $attributes->class(['size-8' => ! $sized]) }}
     viewBox="0 0 64 64" fill="none" aria-hidden="true" focusable="false">
    <path d="M12 52V32a20 20 0 0 1 40 0v20"
          stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
    <g fill="currentColor">
        <rect x="19.75" y="32" width="5.5" height="23" rx="2.75"/>
        <rect x="29.25" y="40" width="5.5" height="15" rx="2.75"/>
        <rect x="38.75" y="46" width="5.5" height="9" rx="2.75"/>
    </g>
</svg>
