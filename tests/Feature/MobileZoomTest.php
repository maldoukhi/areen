<?php

declare(strict_types=1);

/*
 * iOS Safari zooms the page when it focuses a field rendering below 16px, and
 * does not zoom back out. The stylesheet floors controls on touch screens so
 * that never fires — and the viewport must keep pinch zoom, because taking it
 * away would fix our font sizing at the cost of anyone who zooms to read.
 */

it('never disables pinch zoom on any page', function (): void {
    foreach (['/', '/programs', '/exercises', '/offline', '/admin/login'] as $path) {
        $html = $this->get($path)->getContent();

        expect($html)->not->toContain('user-scalable')
            ->and($html)->not->toContain('maximum-scale');
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
