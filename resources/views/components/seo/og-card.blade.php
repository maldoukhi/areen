{{--
  The share card, drawn by Chromium at exactly 1200×630 and cached as a PNG.
  See App\Support\OpenGraphImage.

  Rendered from a bare file on disk with no server behind it, so everything is
  inline: the fonts, the logo and the stylesheet. Nothing here may reference a
  URL, and nothing here is translated at render time beyond what the caller
  already resolved — the club's own words are the content.

  Colours are DESIGN.md tokens spelled out literally, because this document
  never loads app.css and Tailwind is not in the picture.
--}}
<!doctype html>
<html lang="{{ $lang }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <style>
        {!! $fontFaces !!}

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            width: {{ $width }}px;
            height: {{ $height }}px;
            overflow: hidden;
        }

        body {
            /* ink-950, with the same brand wash the home hero carries. */
            background:
                radial-gradient(60% 60% at 50% 0%, rgba(97, 181, 209, 0.16), transparent 70%),
                #101F24;
            color: #F2F5F6;
            font-family: 'IBM Plex Sans Arabic', system-ui, sans-serif;
            /* Tracking breaks Arabic letter joining (DESIGN.md §3). */
            letter-spacing: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 40px;
            padding: 72px;
            text-align: center;
        }

        .logo {
            /* DESIGN.md §7: the club logo sits on ink-900 or ink-950, never on
               brand, and is never recoloured, rotated or given a shadow. */
            height: 200px;
            width: auto;
            max-width: 480px;
            object-fit: contain;
        }

        .name {
            font-size: 68px;
            font-weight: 700;
            line-height: 1.15;
            color: #F2F5F6;
        }

        .tagline {
            font-size: 34px;
            font-weight: 400;
            line-height: 1.5;
            color: #9BB0B6;
        }

        .rule {
            width: 120px;
            height: 4px;
            border-radius: 2px;
            background: #61B5D1;
        }
    </style>
</head>
<body>
    @if ($logoDataUri)
        <img class="logo" src="{{ $logoDataUri }}" alt="">
    @endif

    <div>
        <p class="name">{{ $clubName }}</p>
    </div>

    <div class="rule"></div>

    <p class="tagline">{{ $tagline }}</p>
</body>
</html>
