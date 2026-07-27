<?php

declare(strict_types=1);

/*
 * Two separate things stop the page moving under a thumb.
 *
 * The 16px floor on form controls removes the reason Safari zooms on focus at
 * all, and it is the only one of the two that works on iOS — Safari has ignored
 * `user-scalable=no` since iOS 10.
 *
 * The viewport lock is the product owner's decision, taken with the
 * accessibility cost stated: it denies pinch zoom to anyone who zooms in order
 * to read, and Lighthouse scores it as a failure. It binds Android only.
 */

it('locks zoom off on every page', function (): void {
    foreach (['/', '/programs', '/exercises', '/offline', '/admin/login'] as $path) {
        $html = $this->get($path)->getContent();

        expect($html)->toContain('user-scalable=no')
            ->and($html)->toContain('maximum-scale=1');
    }
});

it('keeps viewport-fit=cover so the safe areas still resolve', function (): void {
    $this->get('/')->assertSee('viewport-fit=cover', escape: false);
});

it('floors form controls at 16px on touch screens', function (): void {
    $css = collect(glob(public_path('build/assets/app-*.css')))->first();

    expect($css)->not->toBeNull('run npm run build first');

    $contents = file_get_contents($css);

    expect($contents)->toContain('max(16px,1em)')
        ->and($contents)->toContain('touch-action:manipulation');
});
