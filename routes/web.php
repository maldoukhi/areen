<?php

declare(strict_types=1);

use App\Http\Controllers\ManifestController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::view('/offline', 'offline')->name('offline');

Route::get('/manifest.json', ManifestController::class)->name('manifest');

Route::post('/locale/{locale}', function (string $locale) {
    abort_unless(array_key_exists($locale, config('areen.locales')), 404);

    session()->put('locale', $locale);

    return back();
})->name('locale.switch');
