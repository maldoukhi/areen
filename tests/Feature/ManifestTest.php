<?php

declare(strict_types=1);

it('serves an Arabic right-to-left manifest by default', function (): void {
    $this->getJson('/manifest.json')
        ->assertOk()
        ->assertJsonPath('lang', 'ar')
        ->assertJsonPath('dir', 'rtl')
        ->assertJsonPath('display', 'standalone')
        ->assertJsonPath('start_url', '/')
        ->assertJsonPath('scope', '/')
        ->assertJsonPath('theme_color', '#1A2E34');
});

it('follows the chosen locale', function (): void {
    $this->withSession(['locale' => 'en'])
        ->getJson('/manifest.json')
        ->assertJsonPath('lang', 'en')
        ->assertJsonPath('dir', 'ltr');
});

it('ships a maskable icon so Android does not crop the mark', function (): void {
    $icons = $this->getJson('/manifest.json')->json('icons');

    expect(collect($icons)->pluck('purpose'))->toContain('maskable');
});

it('is served as a manifest document', function (): void {
    $this->get('/manifest.json')
        ->assertHeader('Content-Type', 'application/manifest+json; charset=utf-8');
});
