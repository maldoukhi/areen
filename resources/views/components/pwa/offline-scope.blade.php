@props([
    // Page URLs to keep offline — the program's days.
    'pages' => [],
    // Media URLs to keep offline — exercise stills, GIFs, YouTube thumbnails.
    'media' => [],
])

{{--
  Optional: name the exact offline scope for this program.

  `resources/js/pwa/offline-scope.js` already works without this. On a program
  page it reads the day links out of the DOM, fetches each one, and pulls the
  pictures it finds on them into the worker's runtime caches — so a trainee who
  opens their program in the car park has the whole plan in the basement.

  Drop this component in when the view knows better than the DOM does — a day
  that is not linked from the page, or media that only appears after a tap:

      <x-pwa.offline-scope :pages="$program->days->map(fn ($day) => route('programs.day', [$program, $day->day_number]))"/>

  Whichever list is given wins outright for its kind; an empty list falls back to
  discovery. The scope is always one program, never the site.
--}}

@php
    $payload = [
        'pages' => array_values(array_filter(array_map('strval', collect($pages)->all()))),
        'media' => array_values(array_filter(array_map('strval', collect($media)->all()))),
    ];
@endphp

@if ($payload['pages'] || $payload['media'])
    {{-- `application/json` is data, not script; the HEX flags keep a stray `</script>` in a filename inert. --}}
    <script type="application/json" data-areen-offline-scope>@json($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)</script>
@endif
