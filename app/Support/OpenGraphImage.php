<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Symfony\Component\Process\Process;

/**
 * The 1200×630 card a link to Areen unfurls into on WhatsApp, X or Slack.
 *
 * It is drawn from the club's own identity in `settings` — the logo, the name,
 * the tagline — because nothing about the club may be written into the code
 * (CLAUDE.md §1). Handing the platform to a second club therefore changes the
 * share card too, with no asset to re-export.
 *
 * It is rendered by the Chromium binary this project already drives for the PDF
 * download rather than by an imaging package, for exactly the reason
 * {@see PdfRenderer} gives: the browser is already installed, it shapes Arabic
 * correctly, and it costs no new dependency. The HTML is fully self-contained —
 * the logo and the two font weights are inlined as `data:` URIs — because the
 * render happens from a bare file on disk with no server behind it.
 *
 * The result is cached on the storage disk under a fingerprint of the club
 * identity that produced it, so it is drawn once and redrawn only when the
 * settings row or the logo file actually changes.
 */
final class OpenGraphImage
{
    public const int WIDTH = 1200;

    public const int HEIGHT = 630;

    private const int TIMEOUT_SECONDS = 30;

    /**
     * Bump when the card's design changes, so every cached copy is superseded
     * without anyone having to clear a directory by hand.
     */
    private const string TEMPLATE_VERSION = 'v1';

    /**
     * The public URL of the card, fingerprinted so a changed logo or club name
     * busts every cache between here and the reader's chat app.
     */
    public static function url(): string
    {
        return route('seo.og-image', ['v' => self::fingerprint()]);
    }

    /**
     * The card as PNG bytes, or null if it could not be drawn — the caller
     * falls back to a shipped icon rather than serving a broken image.
     */
    public static function bytes(): ?string
    {
        $path = self::cachePath();

        if (is_file($path) && filesize($path) > 0) {
            return file_get_contents($path) ?: null;
        }

        $png = self::draw();

        if ($png === null) {
            return null;
        }

        $directory = dirname($path);

        if (! is_dir($directory)) {
            @mkdir($directory, 0o755, true);
        }

        @file_put_contents($path, $png);

        return $png;
    }

    /**
     * What the card is a picture of. Anything that changes it changes this.
     */
    public static function fingerprint(): string
    {
        $club = rescue(fn (): ?Setting => Setting::current(), null, false);
        $logo = rescue(fn (): ?string => $club?->logoFilePath(), null, false);

        return substr(hash('sha256', implode('|', [
            self::TEMPLATE_VERSION,
            app()->getLocale(),
            (string) $club?->club_name,
            (string) $club?->tagline,
            (string) $logo,
            $logo !== null && is_file($logo) ? (string) filemtime($logo) : '',
        ])), 0, 12);
    }

    private static function cachePath(): string
    {
        return storage_path('app/og/'.self::fingerprint().'.png');
    }

    private static function draw(): ?string
    {
        $binary = PdfRenderer::binary();

        if ($binary === null) {
            Log::warning('OpenGraphImage: no Chromium binary found; set CHROMIUM_PATH.');

            return null;
        }

        $club = rescue(fn (): ?Setting => Setting::current(), null, false);

        $html = View::make('components.seo.og-card', [
            'clubName' => trim((string) ($club?->club_name ?? '')) ?: __('common.app_name'),
            'tagline' => trim((string) ($club?->tagline ?? '')) ?: __('common.tagline'),
            'logoDataUri' => self::dataUri(rescue(fn (): ?string => $club?->logoFilePath(), null, false)),
            'fontFaces' => self::fontFaces(),
            'dir' => config('areen.locales.'.app()->getLocale().'.dir', 'rtl'),
            'lang' => app()->getLocale(),
            'width' => self::WIDTH,
            'height' => self::HEIGHT,
        ])->render();

        $id = bin2hex(random_bytes(8));
        $base = sys_get_temp_dir().'/areen-og-'.$id;
        $htmlPath = $base.'.html';
        $pngPath = $base.'.png';
        $profileDir = $base.'-profile';

        file_put_contents($htmlPath, $html);

        try {
            $process = new Process([
                $binary,
                '--headless',
                '--disable-gpu',
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--disable-extensions',
                '--hide-scrollbars',
                '--force-device-scale-factor=1',
                '--user-data-dir='.$profileDir,
                '--window-size='.self::WIDTH.','.self::HEIGHT,
                '--virtual-time-budget=6000',
                '--screenshot='.$pngPath,
                'file://'.$htmlPath,
            ]);

            $process->setTimeout(self::TIMEOUT_SECONDS);
            $process->run();

            if (! is_file($pngPath) || filesize($pngPath) === 0) {
                Log::warning('OpenGraphImage: Chromium did not produce a PNG.', [
                    'exit_code' => $process->getExitCode(),
                    'error_output' => mb_substr($process->getErrorOutput(), -2000),
                ]);

                return null;
            }

            return file_get_contents($pngPath) ?: null;
        } finally {
            @unlink($htmlPath);
            @unlink($pngPath);
        }
    }

    /**
     * The two weights the card uses, inlined. Without them Chromium falls back
     * to whatever the host happens to have, and on a bare server that means
     * Arabic drawn in a font with no proper joining.
     */
    private static function fontFaces(): string
    {
        $faces = '';

        foreach ([400, 700] as $weight) {
            foreach (['arabic', 'latin'] as $script) {
                $file = public_path("fonts/plex-arabic-{$script}-{$weight}.woff2");

                if (! is_file($file)) {
                    continue;
                }

                $faces .= sprintf(
                    "@font-face{font-family:'IBM Plex Sans Arabic';font-style:normal;font-weight:%d;font-display:block;src:url(data:font/woff2;base64,%s) format('woff2');}",
                    $weight,
                    base64_encode((string) file_get_contents($file)),
                );
            }
        }

        return $faces;
    }

    private static function dataUri(?string $path): ?string
    {
        if ($path === null || ! is_file($path)) {
            return null;
        }

        $contents = @file_get_contents($path);

        if ($contents === false || $contents === '') {
            return null;
        }

        return 'data:'.(mime_content_type($path) ?: 'image/png').';base64,'.base64_encode($contents);
    }
}
