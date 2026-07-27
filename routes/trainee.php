<?php

declare(strict_types=1);

use App\Http\Controllers\Api\WorkoutLogSyncController;
use App\Http\Controllers\Trainee\BodyMetricController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Trainee
|--------------------------------------------------------------------------
|
| The signed-in trainee's own three screens, plus the endpoint the offline
| queue drains into. Kept in their own file — like the panel — so the surface
| a member can reach is reviewable on its own.
|
| Registered from App\Providers\AppServiceProvider under the `web` group, which
| is what gives the sync endpoint the session it authenticates with. It is a
| JSON endpoint but not a public API: there is no token, no `/api` prefix and
| no stateless guard, because its only caller is a page this trainee already
| has open, carrying the session cookie and the CSRF token that came with it.
|
| `auth` alone guards everything. There is no `can:` gate here on purpose —
| every screen shows the viewer's own rows and nobody else's, and that is
| enforced by policy at the point of reading and writing rather than by a role
| check at the door, which would be a second place to forget.
|
*/

Route::middleware('auth')->group(function (): void {
    Route::livewire('/dashboard', 'pages::dashboard.index')->name('dashboard');
    Route::livewire('/dashboard/log', 'pages::dashboard.log')->name('dashboard.log');
    Route::livewire('/dashboard/progress', 'pages::dashboard.progress')->name('dashboard.progress');

    /*
     | The offline queue's landing strip. It upserts on `client_uuid`, so the
     | browser is free to send the same batch as many times as it likes — which
     | it will, because Background Sync retries and the in-page fallbacks can
     | fire together.
     */
    Route::post('/dashboard/workout-logs/sync', WorkoutLogSyncController::class)
        ->name('dashboard.logs.sync');

    Route::post('/dashboard/body-metrics', [BodyMetricController::class, 'store'])
        ->name('dashboard.metrics.store');
});
