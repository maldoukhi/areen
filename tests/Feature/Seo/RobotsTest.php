<?php

declare(strict_types=1);

it('serves robots.txt from the app so the sitemap line names the live host', function (): void {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
        ->assertSee('Sitemap: '.route('seo.sitemap'), escape: false);
});

it('closes every surface that is not the public catalogue', function (string $rule): void {
    $this->get('/robots.txt')->assertOk()->assertSee($rule, escape: false);
})->with([
    'Disallow: /admin',
    'Disallow: /dashboard',
    // The access code in the path is a credential; a crawler must never follow one.
    'Disallow: /p/',
    // Matches `/livewire-<8 hex>/update` as a prefix, so APP_KEY never has to be known here.
    'Disallow: /livewire',
]);

/*
 * A static public/robots.txt would be served by the web server before any route
 * is consulted, silently, with whatever hostname was written into it. The file
 * was deleted for that reason and must stay deleted.
 */
it('has no static robots.txt shadowing the route', function (): void {
    expect(file_exists(public_path('robots.txt')))->toBeFalse(
        'public/robots.txt is back — it will shadow RobotsController on a real web server',
    );
});
