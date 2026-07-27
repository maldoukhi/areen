<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Program;
use App\Support\ProgramPrintPresenter;
use Illuminate\Contracts\View\View;

/**
 * The printable schedule at `/programs/{program}/print` — a plain white page,
 * one table per day, meant to come out of a home printer (DESIGN.md §8).
 *
 * Access control is not this controller's job: `EnsureProgramIsViewable`
 * guards the route in `routes/print.php` before this ever runs, the same way
 * it guards the overview and the day page, so a private program cannot be
 * printed by anyone who has not opened it with its access code first.
 */
class ProgramPrintController extends Controller
{
    public function __invoke(Program $program): View
    {
        return view('print.program', ProgramPrintPresenter::forProgram($program));
    }
}
