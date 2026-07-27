@php
    // Same derivation the live app layout uses (resources/views/layouts/app.blade.php),
    // kept independent rather than shared: this document never extends that
    // layout, so RTL/LTR and the printed sheet's own <head> have to stand on
    // their own.
    $locale = app()->getLocale();
    $dir = config("areen.locales.{$locale}.dir", 'rtl');
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('common.app_name'))</title>
    <style>{!! $printCss !!}</style>
</head>
<body>
    @yield('content')
</body>
</html>
