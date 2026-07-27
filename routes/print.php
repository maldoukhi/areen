<?php

declare(strict_types=1);

use App\Http\Controllers\ProgramPdfController;
use App\Http\Controllers\ProgramPrintController;
use App\Http\Middleware\EnsureProgramIsViewable;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Print & PDF (P6)
|--------------------------------------------------------------------------
|
| Same guard as `programs.show` and `programs.day`, and for the same reason:
| a private program's schedule must not become readable one route deeper just
| because this one forgot to check. `EnsureProgramIsViewable` reports a
| private program as missing rather than forbidden, so neither response
| confirms the slug exists to a visitor who has not unlocked it.
*/

Route::middleware(EnsureProgramIsViewable::class)->group(function (): void {
    Route::get('/programs/{program}/print', ProgramPrintController::class)->name('programs.print');
    Route::get('/programs/{program}/pdf', ProgramPdfController::class)->name('programs.pdf');
});
