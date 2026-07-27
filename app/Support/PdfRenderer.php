<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Turns an already-rendered HTML string into PDF bytes by driving the
 * Chromium binary installed in this environment directly — no Browsershot,
 * no Node, no Puppeteer.
 *
 * `spatie/browsershot` was the obvious first choice, but it shells out to a
 * small Node script and therefore needs `puppeteer` (and so Node itself)
 * installed alongside it — a second language runtime pulled in for one PDF
 * button. Chromium is already present in this environment, and it accepts
 * `--headless --print-to-pdf` on the command line with no Node in between,
 * so driving it with {@see Process} (already a Laravel dependency, nothing
 * new to install) gets the same rendering engine Browsershot would have
 * used — correct Arabic shaping and RTL included — for zero new
 * dependencies.
 *
 * If no usable Chromium binary is found, {@see self::renderPdf()} returns
 * null rather than throwing, so a caller can show an honest "PDF is not
 * available here" message instead of a broken download.
 */
final class PdfRenderer
{
    /**
     * Checked in order after `CHROMIUM_PATH`. Both are real locations in
     * this project's environment (a versioned install and the convenience
     * symlink beside it); a production host is expected to set
     * `CHROMIUM_PATH` instead of relying on either.
     *
     * @var list<string>
     */
    private const array CANDIDATE_PATHS = [
        '/opt/pw-browsers/chromium-1194/chrome-linux/chrome',
        '/opt/pw-browsers/chromium/chrome-linux/chrome',
    ];

    private const int TIMEOUT_SECONDS = 30;

    public static function isAvailable(): bool
    {
        return self::binaryPath() !== null;
    }

    /**
     * Renders `$html` to a PDF and returns the raw bytes, or null if no
     * Chromium binary is available or the render failed. The HTML is
     * expected to be fully self-contained (inline CSS, inline images) —
     * this loads it from a bare temp file with no server behind it, so any
     * relative URL inside it would have nothing to resolve against.
     */
    public static function renderPdf(string $html): ?string
    {
        $binary = self::binaryPath();

        if ($binary === null) {
            Log::warning('PdfRenderer: no Chromium binary found; set CHROMIUM_PATH.');

            return null;
        }

        $id = bin2hex(random_bytes(8));
        $base = sys_get_temp_dir().'/areen-print-'.$id;
        $htmlPath = $base.'.html';
        $pdfPath = $base.'.pdf';
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
                '--no-pdf-header-footer',
                '--user-data-dir='.$profileDir,
                '--virtual-time-budget=8000',
                '--print-to-pdf='.$pdfPath,
                'file://'.$htmlPath,
            ]);

            $process->setTimeout(self::TIMEOUT_SECONDS);
            $process->run();

            if (! $process->isSuccessful() || ! is_file($pdfPath) || filesize($pdfPath) === 0) {
                Log::warning('PdfRenderer: Chromium did not produce a PDF.', [
                    'exit_code' => $process->getExitCode(),
                    'error_output' => mb_substr($process->getErrorOutput(), -2000),
                ]);

                return null;
            }

            return file_get_contents($pdfPath) ?: null;
        } finally {
            @unlink($htmlPath);
            @unlink($pdfPath);
            self::removeDirectory($profileDir);
        }
    }

    /**
     * `CHROMIUM_PATH`, when set, is treated as an explicit instruction rather
     * than one more guess to try: a deployment that points it at the wrong
     * place should see PDF generation go unavailable, not quietly succeed
     * from an unrelated Chromium it never asked for. The bundled candidate
     * paths are only consulted when the variable is unset entirely.
     */
    private static function binaryPath(): ?string
    {
        $configured = env('CHROMIUM_PATH');

        if (is_string($configured) && $configured !== '') {
            return self::isUsableBinary($configured) ? $configured : null;
        }

        foreach (self::CANDIDATE_PATHS as $path) {
            if (self::isUsableBinary($path)) {
                return $path;
            }
        }

        return null;
    }

    private static function isUsableBinary(string $path): bool
    {
        return is_file($path) && is_executable($path);
    }

    private static function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory) ?: [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.'/'.$item;

            is_dir($path) ? self::removeDirectory($path) : @unlink($path);
        }

        @rmdir($directory);
    }
}
