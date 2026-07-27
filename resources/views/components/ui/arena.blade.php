@props(['settings' => null])

{{--
  The hero visual: the club's own crest, set in a lit arena.

  Concentric tracks give it depth, one arc sweeps the outer ring, and a soft
  brand wash sits behind the crest so it reads as lit rather than pasted on. The
  rings are drawn as a background, never a shadow — DESIGN.md keeps elevation in
  the surface on dark.

  Decorative, so it is hidden from assistive technology, and the sweep stops
  under `prefers-reduced-motion` (see app.css).
--}}
<div {{ $attributes->class('relative isolate aspect-square') }} aria-hidden="true">
    <svg viewBox="0 0 400 400" fill="none" class="absolute inset-0 size-full">
        <defs>
            <linearGradient id="areen-arc" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="var(--color-brand-400)" stop-opacity="0.9"/>
                <stop offset="55%" stop-color="var(--color-brand-400)" stop-opacity="0.2"/>
                <stop offset="100%" stop-color="var(--color-brand-400)" stop-opacity="0"/>
            </linearGradient>

            <radialGradient id="areen-wash" cx="50%" cy="50%" r="50%">
                <stop offset="0%" stop-color="var(--color-brand-400)" stop-opacity="0.18"/>
                <stop offset="65%" stop-color="var(--color-brand-400)" stop-opacity="0.04"/>
                <stop offset="100%" stop-color="var(--color-brand-400)" stop-opacity="0"/>
            </radialGradient>
        </defs>

        <circle cx="200" cy="200" r="196" fill="url(#areen-wash)"/>

        <circle cx="200" cy="200" r="190" stroke="var(--color-ink-800)" stroke-width="1"/>
        <circle cx="200" cy="200" r="158" stroke="var(--color-ink-800)" stroke-width="1"/>
        <circle cx="200" cy="200" r="126" stroke="var(--color-ink-800)" stroke-width="1"/>

        {{-- Lane ticks: the detail that makes the rings read as a track. --}}
        <g stroke="var(--color-ink-700)" stroke-width="1.5" stroke-linecap="round">
            @for ($tick = 0; $tick < 24; $tick++)
                <line x1="200" y1="10" x2="200" y2="22"
                      transform="rotate({{ $tick * 15 }} 200 200)"
                      opacity="{{ $tick % 6 === 0 ? '0.9' : '0.35' }}"/>
            @endfor
        </g>

        <g class="areen-sweep">
            <circle cx="200" cy="200" r="190"
                    stroke="url(#areen-arc)" stroke-width="2.5"
                    stroke-linecap="round" stroke-dasharray="340 854"/>
        </g>
    </svg>

    {{--
      The crest sits inside the innermost track. Its width is a fraction of the
      arena rather than an inset, so the rings stay visible around it at every
      size and the geometry does not depend on a percentage inset resolving.
    --}}
    <div class="absolute inset-0 grid place-items-center">
        <x-brand.club-logo :settings="$settings"
                           size="w-[46%] h-auto object-contain"
                           mark-class="w-[46%] h-auto text-brand-400"/>
    </div>
</div>
