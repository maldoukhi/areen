<?php

declare(strict_types=1);

use App\Http\Controllers\ManifestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::livewire('/', 'pages::home')->name('home');

Route::livewire('/programs', 'pages::programs.index')->name('programs.index');
Route::livewire('/programs/{program}', 'pages::programs.show')->name('programs.show');
Route::livewire('/programs/{program}/day/{day}', 'pages::programs.day')->name('programs.day');

Route::livewire('/exercises', 'pages::exercises.index')->name('exercises.index');
Route::livewire('/exercises/{exercise}', 'pages::exercises.show')->name('exercises.show');

Route::livewire('/muscles/{muscleGroup}', 'pages::muscles.show')->name('muscles.show');

/*
 * A private program's only door. The code is the credential, so this route is
 * deliberately separate from `programs.show`, which refuses anything unpublished.
 */
Route::livewire('/p/{accessCode}', 'pages::programs.private')->name('programs.private');

Route::livewire('/about', 'pages::about')->name('about');

Route::view('/offline', 'offline')->name('offline');

/*
|--------------------------------------------------------------------------
| System
|--------------------------------------------------------------------------
*/

Route::get('/manifest.json', ManifestController::class)->name('manifest');

Route::post('/locale/{locale}', function (string $locale) {
    abort_unless(array_key_exists($locale, config('areen.locales')), 404);

    session()->put('locale', $locale);

    return back();
})->name('locale.switch');
