<?php

declare(strict_types=1);
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Program;
use App\Models\ProgramDay;

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

/*
 * Every page type, not only the home page. A page that reads its own title, its
 * own model fields or its own layout can each drop the locale on the floor in a
 * different way, and each of those was a separate bug waiting.
 */
it('serves every page type in the reader chosen language', function (string $path): void {
    $muscle = MuscleGroup::factory()->named('chest')->create();

    Exercise::factory()->for($muscle)->create([
        'slug' => 'locale-press',
        'name_ar' => 'ضغط عربي',
        'name_en' => 'English Press',
    ]);

    $program = Program::factory()->published()->create([
        'slug' => 'locale-plan',
        'name_ar' => 'برنامج عربي',
        'name_en' => 'English Plan',
        'days_count' => 1,
    ]);

    ProgramDay::factory()->for($program)->create(['day_number' => 1]);

    Program::factory()->create([
        'slug' => 'locale-private',
        'is_public' => false,
        'published_at' => null,
        'access_code' => 'LOCALE01',
    ]);

    $this->get($path)->assertOk()->assertSee('<html lang="ar" dir="rtl"', escape: false);

    $this->withSession(['locale' => 'en'])
        ->get($path)
        ->assertOk()
        ->assertSee('<html lang="en" dir="ltr"', escape: false);
})->with([
    '/',
    '/programs',
    '/programs/locale-plan',
    '/programs/locale-plan/day/1',
    '/exercises',
    '/exercises/locale-press',
    '/muscles/chest',
    '/p/LOCALE01',
    '/about',
    '/offline',
]);

it('reads the model translation that matches the language', function (): void {
    Program::factory()->published()->create([
        'slug' => 'bilingual-plan',
        'name_ar' => 'برنامج عربي',
        'name_en' => 'English Plan',
    ]);

    $this->get('/programs/bilingual-plan')->assertOk()->assertSee('برنامج عربي');

    $this->withSession(['locale' => 'en'])
        ->get('/programs/bilingual-plan')
        ->assertOk()
        ->assertSee('English Plan')
        ->assertDontSee('برنامج عربي');
});

it('follows the manifest to the reader language', function (): void {
    $this->get('/manifest.json')->assertOk()->assertJsonPath('lang', 'ar')->assertJsonPath('dir', 'rtl');

    $this->withSession(['locale' => 'en'])
        ->get('/manifest.json')
        ->assertOk()
        ->assertJsonPath('lang', 'en')
        ->assertJsonPath('dir', 'ltr');
});

/*
 * The `?lang=` parameter exists so `hreflang` can point at distinct addresses
 * (App\Support\Seo). It has to pin the page AND stick, or a reader arriving on
 * an English search result falls back to Arabic on their next click.
 */
it('pins the language from the address and remembers the choice', function (): void {
    $this->get('/programs?lang=en')
        ->assertOk()
        ->assertSee('<html lang="en" dir="ltr"', escape: false)
        ->assertSessionHas('locale', 'en');

    $this->get('/programs')->assertOk()->assertSee('<html lang="en" dir="ltr"', escape: false);
});

it('ignores a language in the address that the platform does not support', function (): void {
    $this->get('/?lang=fr')
        ->assertOk()
        ->assertSee('<html lang="ar" dir="rtl"', escape: false);
});
