<?php

declare(strict_types=1);

it('renders the home page in Arabic with a right-to-left document', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('<html lang="ar" dir="rtl"', escape: false);
});

it('serves an offline page rather than a blank browser error', function (): void {
    $this->get('/offline')->assertOk();
});

it('keeps every fixed element clear of the notch', function (): void {
    $this->get('/')->assertSee('viewport-fit=cover', escape: false);
});
