<?php

declare(strict_types=1);
use App\Models\Setting;

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

it('names the installed app after the club, not the platform', function (): void {
    Setting::query()->create(['club_name_ar' => 'نادي الاختبار']);
    Setting::forget();

    expect($this->getJson('/manifest.json')->json('name'))->toBe('نادي الاختبار');
});

it('shortens a multi-word club name to the word that identifies it', function (): void {
    Setting::query()->create(['club_name_ar' => 'قسورة الأزرق']);
    Setting::forget();

    expect($this->getJson('/manifest.json')->json('short_name'))->toBe('قسورة');
});

it('falls back to the platform name when no club has been set up', function (): void {
    Setting::query()->delete();
    Setting::forget();

    expect($this->getJson('/manifest.json')->json('name'))->toBe(__('common.app_name'));
});

/*
 * A launcher keeps an icon it already has for a URL it has already seen, so a
 * regenerated icon only reaches the home screen if its URL changes with it.
 */
it('fingerprints every icon url so a new icon is actually fetched', function (): void {
    $icons = $this->getJson('/manifest.json')->json('icons');

    expect($icons)->not->toBeEmpty();

    foreach ($icons as $icon) {
        expect($icon['src'])->toMatch('#\?v=[0-9a-f]{8}$#');
    }
});

it('declares only icons that exist on disk at the size claimed', function (): void {
    foreach ($this->getJson('/manifest.json')->json('icons') as $icon) {
        $path = public_path(parse_url($icon['src'], PHP_URL_PATH));

        expect(is_file($path))->toBeTrue("missing {$icon['src']}");

        [$width, $height] = getimagesize($path);

        expect("{$width}x{$height}")->toBe($icon['sizes'], "{$icon['src']} is not {$icon['sizes']}");
    }
});

it('does not let the manifest be cached, so a rename shows up', function (): void {
    $this->get('/manifest.json')->assertHeader('Cache-Control', 'must-revalidate, no-cache, private');
});
