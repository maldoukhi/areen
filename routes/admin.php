<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
|
| Kept in its own file rather than in web.php so the public surface and the
| managed one can be reasoned about — and reviewed — separately. Registered in
| bootstrap/app.php under the `web` group with the `/admin` prefix.
|
*/

Route::middleware(['auth'])->group(function (): void {
    // P3 fills this in: dashboard, programs and the day builder, exercises,
    // muscle groups, trainees and access codes, club settings.
});
