@props([
    'description' => null,
    'image' => null,
    'type' => 'website',
    'noindex' => false,
    'canonical' => null,
])

{{--
  A page describing itself. Renders nothing — it records what it was given so
  `<x-seo.meta>` in the layout can draw one coherent block of tags instead of
  each page emitting its own half-set.

  Pages push it into the head stack:

      @push('head')
          <x-seo.page :description="$program->description" type="article"/>
      @endpush

  Blade renders the body of a `@push` immediately, before the layout it will
  end up inside, so the declaration is always on record by the time the layout
  asks for it. The title is deliberately NOT a prop: the layout already has the
  document title and hands it over itself, which is what keeps `og:title` and
  the browser tab from ever saying two different things.
--}}

@php
    App\Support\Seo::declare(
        description: $description,
        image: $image,
        type: $type,
        noindex: (bool) $noindex,
        canonical: $canonical,
    );
@endphp
