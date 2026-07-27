<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Program;
use App\Support\PdfRenderer;
use App\Support\ProgramPrintPresenter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

/**
 * The PDF download at `/programs/{program}/pdf`.
 *
 * Renders the exact same `print.program` view the browser-viewable print
 * sheet uses, then hands the resulting HTML to {@see PdfRenderer}, which
 * shells out to Chromium's own `--print-to-pdf`. If no Chromium binary can
 * be found in this environment, this degrades honestly: it serves the same
 * print page back with a plain notice explaining that a PDF could not be
 * generated here, rather than a corrupt file pretending to be one.
 *
 * Access control is the route's job, not this controller's: see
 * `routes/print.php`.
 */
class ProgramPdfController extends Controller
{
    public function __invoke(Program $program): Response
    {
        $data = ProgramPrintPresenter::forProgram($program);

        $pdf = PdfRenderer::renderPdf(View::make('print.program', $data)->render());

        if ($pdf !== null) {
            $filename = Str::slug($program->name) ?: 'program';

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'.pdf"',
            ]);
        }

        return response()
            ->view('print.program', [...$data, 'pdfUnavailable' => true])
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
