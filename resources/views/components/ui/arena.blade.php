{{--
  The athletic motif: concentric arcs taken from the arch in the Areen mark,
  read as a stadium seen from above. The outer ring sweeps slowly; the inner
  ones hold still, so the shape reads as depth rather than as a spinner.

  Purely decorative, so it is hidden from assistive technology, and the sweep
  stops under `prefers-reduced-motion` (handled in app.css).
--}}
<svg {{ $attributes->class('pointer-events-none select-none') }}
     viewBox="0 0 400 400" fill="none" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="areen-arc" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="var(--color-brand-400)" stop-opacity="0.85"/>
            <stop offset="60%" stop-color="var(--color-brand-400)" stop-opacity="0.15"/>
            <stop offset="100%" stop-color="var(--color-brand-400)" stop-opacity="0"/>
        </linearGradient>
    </defs>

    <circle cx="200" cy="200" r="186" stroke="var(--color-ink-800)" stroke-width="1"/>
    <circle cx="200" cy="200" r="150" stroke="var(--color-ink-800)" stroke-width="1"/>
    <circle cx="200" cy="200" r="112" stroke="var(--color-ink-800)" stroke-width="1"/>

    {{-- The moving element: one open arc riding the outermost track. --}}
    <g class="areen-sweep">
        <circle cx="200" cy="200" r="186"
                stroke="url(#areen-arc)" stroke-width="2"
                stroke-linecap="round" stroke-dasharray="300 869"/>
    </g>

    {{-- The mark's arch at the centre, scaled up and drawn as an outline. --}}
    <g transform="translate(136 136) scale(2)">
        <path d="M12 52V32a20 20 0 0 1 40 0v20"
              stroke="var(--color-brand-400)" stroke-width="4"
              stroke-linecap="round" opacity="0.9"/>
        <g fill="var(--color-brand-400)" opacity="0.9">
            <rect x="19.75" y="32" width="5.5" height="23" rx="2.75"/>
            <rect x="29.25" y="40" width="5.5" height="15" rx="2.75"/>
            <rect x="38.75" y="46" width="5.5" height="9" rx="2.75"/>
        </g>
    </g>
</svg>
