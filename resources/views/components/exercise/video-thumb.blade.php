@props([
    'exercise' => null,
    'label' => null,
    'eager' => false,
])

{{--
  DESIGN.md §6: a still frame plus a play button. The YouTube iframe is only
  injected once the trainee taps it — that is a performance decision (no third
  party frame on a 4G basement connection) and a privacy one (no request to
  Google until the trainee asks for the video).

  Three states, in order of preference:

    1. A YouTube video  → 16:9, the hqdefault still, a play button, then the iframe.
    2. An exercise still → 4:3 (DESIGN.md §6), no play button.
    3. Neither          → the same 4:3 box, ink-800, with the Areen mark at 6%.

  The third state is the normal one today — every seeded exercise has a null
  `youtube_url` and a null `media_path` — so the placeholder is the baseline and
  the two image states are painted on top of it. That layering is also what keeps
  the promise of "never a broken image": if the network is down and the remote
  still never arrives, the img hides itself and the placeholder underneath is
  already there. Nothing in the layout depends on a byte arriving from outside.
--}}

@php
    $youtubeId = rescue(fn () => $exercise?->youtube_id, null, false);
    $thumbnail = $youtubeId ? rescue(fn () => $exercise?->youtube_thumbnail_url, null, false) : null;

    // Media paths are stored relative to the public disk, but an absolute URL or
    // a root-relative path is passed through untouched — same rule as the club logo.
    $mediaPath = rescue(fn () => $exercise?->media_path, null, false);

    $still = filled($mediaPath)
        ? (\Illuminate\Support\Str::startsWith($mediaPath, ['http://', 'https://', '/'])
            ? $mediaPath
            : rescue(fn () => \Illuminate\Support\Facades\Storage::disk('public')->url($mediaPath), null, false))
        : null;

    $name = $label ?? rescue(fn () => $exercise?->name, null, false);

    $playLabel = trim(__('exercise.media.play').' '.($name ?? ''));

    $loading = $eager ? 'eager' : 'lazy';

    $frame = $youtubeId
        ? 'relative isolate w-full overflow-hidden rounded-md border border-ink-700 bg-ink-800 aspect-video'
        : 'relative isolate w-full overflow-hidden rounded-md border border-ink-700 bg-ink-800 aspect-[4/3]';
@endphp

<div
    @if ($youtubeId) x-data="{ playing: false }" @endif
    {{ $attributes->class([$frame, 'print:hidden']) }}>

    {{-- The baseline layer. Always painted, always local, never a request. --}}
    <div class="absolute inset-0 -z-10 flex items-center justify-center" aria-hidden="true">
        <x-brand.mark class="size-16 text-brand-400 opacity-[0.06]"/>
    </div>

    @if ($youtubeId)
        <button type="button"
                x-show="! playing"
                x-on:click="playing = true"
                aria-label="{{ $playLabel }}"
                class="absolute inset-0 flex size-full min-h-11 items-center justify-center">
            <img src="{{ $thumbnail }}"
                 alt=""
                 loading="{{ $loading }}"
                 decoding="async"
                 class="absolute inset-0 size-full object-cover"
                 x-on:error="$el.hidden = true"
                 x-init="if ($el.complete && $el.naturalWidth === 0) $el.hidden = true">

            <span class="relative inline-flex size-14 items-center justify-center rounded-full bg-brand-400 text-brand-950">
                <x-ui.icon name="play" class="size-7"/>
            </span>
        </button>

        {{-- Injected on tap, never before. `nocookie` keeps the pre-consent request out entirely. --}}
        <template x-if="playing">
            <iframe class="absolute inset-0 size-full border-0"
                    src="https://www.youtube-nocookie.com/embed/{{ $youtubeId }}?autoplay=1&rel=0&playsinline=1"
                    title="{{ trim(__('exercise.media.video').' '.($name ?? '')) }}"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin"
                    allowfullscreen></iframe>
        </template>
    @elseif ($still)
        <img src="{{ $still }}"
             alt="{{ trim(__('exercise.media.image').' '.($name ?? '')) }}"
             loading="{{ $loading }}"
             decoding="async"
             class="absolute inset-0 size-full object-cover"
             x-data
             x-on:error="$el.hidden = true"
             x-init="if ($el.complete && $el.naturalWidth === 0) $el.hidden = true">
    @else
        <span class="sr-only">{{ __('exercise.media.none_title') }}</span>
    @endif
</div>
