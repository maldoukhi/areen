<?php

declare(strict_types=1);

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Program;
use App\Models\ProgramDay;
use App\Models\Setting;

it('describes the home page with the club identity rather than with code', function (): void {
    Setting::factory()->create([
        'club_name_ar' => 'نادي التجربة',
        'club_name_en' => 'Test Club',
        'tagline_ar' => 'شعارنا',
        'description_ar' => 'وصف النادي الكامل.',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('<title>نادي التجربة · شعارنا</title>', escape: false)
        ->assertSee('og:site_name" content="نادي التجربة"', escape: false)
        ->assertSee('<meta name="description"', escape: false);
});

it('gives every public page a canonical URL, a description and a social card', function (string $path): void {
    $program = Program::factory()->published()->create(['slug' => 'meta-plan', 'days_count' => 1]);
    ProgramDay::factory()->for($program)->create(['day_number' => 1]);

    $muscle = MuscleGroup::factory()->named('chest')->create();
    Exercise::factory()->for($muscle)->create(['slug' => 'meta-press']);

    $body = $this->get($path)->assertOk()->getContent();

    expect($body)
        ->toContain('rel="canonical"')
        ->toContain('<meta name="description"')
        ->toContain('property="og:title"')
        ->toContain('property="og:image"')
        ->toContain('name="twitter:card" content="summary_large_image"')
        ->toContain('hreflang="ar"')
        ->toContain('hreflang="en"')
        ->toContain('hreflang="x-default"');
})->with([
    '/',
    '/programs',
    '/programs/meta-plan',
    '/programs/meta-plan/day/1',
    '/exercises',
    '/exercises/meta-press',
    '/muscles/chest',
    '/about',
]);

it('canonicalises a filtered library back to the library', function (): void {
    MuscleGroup::factory()->named('chest')->create();

    $this->get('/exercises?q=press&muscle=chest')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.route('exercises.index').'">', escape: false);
});

it('points each hreflang at a distinct, self-canonical address', function (): void {
    Program::factory()->published()->create(['slug' => 'hreflang-plan']);

    $arabic = $this->get('/programs/hreflang-plan')->assertOk()->getContent();

    expect($arabic)
        ->toContain('<link rel="canonical" href="'.route('programs.show', 'hreflang-plan').'">')
        ->toContain('hreflang="en" href="'.route('programs.show', 'hreflang-plan').'?lang=en"');

    // The same page pinned to English canonicalises to the English address, so
    // the two are alternates of each other rather than duplicates of one.
    $english = $this->get('/programs/hreflang-plan?lang=en')->assertOk()->getContent();

    expect($english)
        ->toContain('<link rel="canonical" href="'.route('programs.show', 'hreflang-plan').'?lang=en"')
        ->toContain('<html lang="en" dir="ltr"');
});

it('keeps a private program out of every index', function (): void {
    $private = Program::factory()->create([
        'slug' => 'meta-private',
        'is_public' => false,
        'published_at' => null,
        'access_code' => 'PRIVATE1',
    ]);

    $this->get('/p/PRIVATE1')
        ->assertOk()
        ->assertSee('name="robots" content="noindex, nofollow"', escape: false);

    // And the slug door still refuses, so the noindex is a third lock, not the only one.
    $this->get('/programs/'.$private->slug)->assertNotFound();
});

it('marks the offline page as not for indexing', function (): void {
    $this->get('/offline')
        ->assertOk()
        ->assertSee('name="robots" content="noindex, nofollow"', escape: false);
});

it('does not mark an ordinary public page as noindex', function (): void {
    $this->get('/')->assertOk()->assertDontSee('noindex', escape: false);
});

/*
 * og:title drifting away from the tab title is the classic way a share preview
 * goes stale, so the layout resolves the title once and hands the same string
 * to both.
 */
it('uses one title for the tab and for the share card', function (): void {
    $program = Program::factory()->published()->create(['slug' => 'one-title']);

    $body = $this->get('/programs/one-title')->assertOk()->getContent();

    preg_match('/<title>(.*?)<\/title>/su', $body, $tab);
    preg_match('/property="og:title" content="(.*?)"/su', $body, $card);

    expect($tab[1] ?? 'a')->toBe($card[1] ?? 'b')
        ->and($tab[1])->toContain($program->name);
});
