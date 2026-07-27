@props(['title'])

{{--
  The whole description of one page: what it is, where it canonically lives,
  which languages it exists in, and what it looks like when someone pastes the
  link into a chat.

  Rendered once, by the layout. A page that wants to say something specific
  pushes `<x-seo.page>`; everything it does not say falls back to the club's own
  identity in `settings`, so a page that says nothing still shares correctly.
--}}

@php
    $seo = App\Support\Seo::tags($title);
@endphp

<meta name="description" content="{{ $seo['description'] }}">

@if ($seo['noindex'])
    <meta name="robots" content="noindex, nofollow">
@endif

<link rel="canonical" href="{{ $seo['canonical'] }}">

{{--
  Locale is a session choice rather than a URL prefix (CLAUDE.md §3), so each
  language is given a distinct, self-canonical address through `?lang=`.
  Pointing several hreflang entries at one URL is invalid, and a crawler has no
  session to carry a preference in.
--}}
@foreach ($seo['alternates'] as $hreflang => $href)
    <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}">
@endforeach

<meta property="og:type" content="{{ $seo['type'] }}">
<meta property="og:site_name" content="{{ $seo['site_name'] }}">
<meta property="og:title" content="{{ $seo['title'] }}">
<meta property="og:description" content="{{ $seo['description'] }}">
<meta property="og:url" content="{{ $seo['canonical'] }}">
<meta property="og:locale" content="{{ $seo['locale'] }}">
@foreach ($seo['alternate_locales'] as $alternateLocale)
    <meta property="og:locale:alternate" content="{{ $alternateLocale }}">
@endforeach
<meta property="og:image" content="{{ $seo['image'] }}">
<meta property="og:image:width" content="{{ App\Support\OpenGraphImage::WIDTH }}">
<meta property="og:image:height" content="{{ App\Support\OpenGraphImage::HEIGHT }}">
<meta property="og:image:alt" content="{{ $seo['image_alt'] }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo['title'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}">
<meta name="twitter:image" content="{{ $seo['image'] }}">
<meta name="twitter:image:alt" content="{{ $seo['image_alt'] }}">
