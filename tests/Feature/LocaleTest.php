<?php

declare(strict_types=1);

it('defaults to Arabic', function (): void {
    $this->get('/');

    expect(app()->getLocale())->toBe('ar');
});

it('stores the chosen locale on the session', function (): void {
    $this->from('/')
        ->post('/locale/en')
        ->assertRedirect('/')
        ->assertSessionHas('locale', 'en');
});

it('flips the document direction for a left-to-right reader', function (): void {
    $this->withSession(['locale' => 'en'])
        ->get('/')
        ->assertSee('<html lang="en" dir="ltr"', escape: false);
});

it('rejects a locale the platform does not support', function (): void {
    $this->post('/locale/fr')->assertNotFound();
});

it('ignores a stale session locale that is no longer supported', function (): void {
    $this->withSession(['locale' => 'fr'])
        ->get('/')
        ->assertSee('<html lang="ar" dir="rtl"', escape: false);
});
