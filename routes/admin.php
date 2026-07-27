<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LogoutController;
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
| Registration is invite-only, so the platform's single sign-in screen lives
| here too. `AppServiceProvider` points every guarded route at it.
|
| Panel routes bind by id, not by slug: an admin who renames a program changes
| its slug, and a URL that stops resolving mid-edit is a bug the reader pays for.
|
*/

Route::middleware('guest')->group(function (): void {
    Route::livewire('/login', 'pages::auth.login')->name('login');
});

Route::post('/logout', LogoutController::class)
    ->middleware('auth')
    ->name('logout');

/*
 | `auth` turns a guest away, `can:access-admin` turns a trainee away. Two
 | different questions, two different answers, and both are needed.
 */
Route::middleware(['auth', 'can:access-admin'])->group(function (): void {
    Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');

    Route::livewire('/programs', 'pages::admin.programs.index')->name('programs.index');
    Route::livewire('/programs/create', 'pages::admin.programs.form')->name('programs.create');
    Route::livewire('/programs/{program:id}/edit', 'pages::admin.programs.form')->name('programs.edit');
    Route::livewire('/programs/{program:id}/days/{day:id}', 'pages::admin.programs.day')->name('programs.day');

    Route::livewire('/exercises', 'pages::admin.exercises.index')->name('exercises.index');
    Route::livewire('/exercises/create', 'pages::admin.exercises.form')->name('exercises.create');
    Route::livewire('/exercises/{exercise:id}/edit', 'pages::admin.exercises.form')->name('exercises.edit');

    Route::livewire('/muscle-groups', 'pages::admin.muscle-groups')->name('muscle-groups.index');

    Route::livewire('/trainees', 'pages::admin.trainees.index')->name('trainees.index');
    Route::livewire('/trainees/{user:id}', 'pages::admin.trainees.show')->name('trainees.show');

    Route::livewire('/settings', 'pages::admin.settings')->name('settings.edit');
});
