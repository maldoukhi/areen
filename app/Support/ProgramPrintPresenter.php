<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Program;
use App\Models\Setting;
use App\Support\Qr\QrCodeGenerator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

/**
 * Builds the one array of view data the print sheet and the PDF both render
 * from, so the printed page and the downloaded PDF can never quietly become
 * two different documents. Each controller only decides how to deliver
 * `print.program` — everything about what goes on the page is decided here.
 */
final class ProgramPrintPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function forProgram(Program $program): array
    {
        $program->loadMissing([
            'days' => fn ($query) => $query->orderBy('day_number'),
            'days.focusMuscle',
            'days.exercises' => fn ($query) => $query->orderBy('sort'),
            'days.exercises.exercise',
        ]);

        $setting = Setting::current();
        $verifyUrl = route('programs.show', $program);

        return [
            'program' => $program,
            'days' => $program->days,
            'setting' => $setting,
            'clubLogoDataUri' => self::logoDataUri($setting),
            'printedAt' => now(),
            'verifyUrl' => $verifyUrl,
            'qrSvg' => QrCodeGenerator::tryToSvg($verifyUrl),
            'printCss' => self::css(),
            'pdfUnavailable' => false,
        ];
    }

    /**
     * The stylesheet as a raw string, inlined into the print layout's own
     * `<style>` tag. It is deliberately not linked: the PDF path renders this
     * same HTML from a bare file on disk with no server behind it, so a
     * linked stylesheet would have nothing to resolve against there. Reading
     * it once here — rather than from inside the Blade template — keeps the
     * view purely presentational.
     */
    public static function css(): HtmlString
    {
        return new HtmlString(file_get_contents(resource_path('css/print.css')) ?: '');
    }

    /**
     * The club logo as a `data:` URI rather than a linked `<img src>`, for
     * the same reason the stylesheet is inlined: the PDF render has no
     * server to fetch `/storage/...` from. Doing it once here means the live
     * print page and the PDF always show the exact same image, not two
     * strategies that could quietly drift apart.
     */
    private static function logoDataUri(Setting $setting): ?string
    {
        // The model owns the question of where a logo lives — a shipped one is a
        // repository asset, an uploaded one is on the storage disk, and asking
        // only the disk is how the printed header lost the club's mark.
        $path = $setting->logoFilePath();

        if ($path === null) {
            return null;
        }

        $contents = @file_get_contents($path);

        if ($contents === false || $contents === '') {
            return null;
        }

        $mimeType = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }
}
