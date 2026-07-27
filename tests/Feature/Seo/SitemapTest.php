<?php

declare(strict_types=1);

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Program;
use App\Models\ProgramDay;

it('lists the public catalogue', function (): void {
    $program = Program::factory()->published()->create(['slug' => 'sitemap-plan', 'days_count' => 2]);

    ProgramDay::factory()->for($program)->create(['day_number' => 1]);
    ProgramDay::factory()->for($program)->create(['day_number' => 2]);

    $muscle = MuscleGroup::factory()->named('chest')->create();
    $exercise = Exercise::factory()->for($muscle)->create(['slug' => 'sitemap-press']);

    $response = $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=utf-8');

    $response->assertSee(route('home'), escape: false)
        ->assertSee(route('programs.index'), escape: false)
        ->assertSee(route('exercises.index'), escape: false)
        ->assertSee(route('about'), escape: false)
        ->assertSee(route('programs.show', $program), escape: false)
        ->assertSee(route('programs.day', ['program' => $program, 'day' => 1]), escape: false)
        ->assertSee(route('programs.day', ['program' => $program, 'day' => 2]), escape: false)
        ->assertSee(route('exercises.show', $exercise), escape: false)
        ->assertSee(route('muscles.show', $muscle), escape: false);
});

/*
 * The whole point of the file. A private program's *name* is the disclosure —
 * it is written for one trainee — so it must not appear even though its day
 * pages are separately guarded.
 */
it('never names a private program', function (): void {
    $private = Program::factory()->create([
        'slug' => 'the-secret-cut',
        'name_ar' => 'برنامج سري',
        'name_en' => 'Secret Cut',
        'is_public' => false,
        'published_at' => null,
        'access_code' => 'SECRET77',
        'days_count' => 3,
    ]);

    ProgramDay::factory()->for($private)->create(['day_number' => 1]);

    $body = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($body)
        ->not->toContain('the-secret-cut')
        ->not->toContain('SECRET77')
        ->not->toContain('Secret Cut')
        ->not->toContain('برنامج سري');
});

it('leaves out a program that is public but not published yet', function (): void {
    Program::factory()->create([
        'slug' => 'scheduled-for-later',
        'is_public' => true,
        'published_at' => now()->addWeek(),
    ]);

    $this->get('/sitemap.xml')->assertOk()->assertDontSee('scheduled-for-later');
});

it('leaves out a trashed program and a trashed exercise', function (): void {
    $program = Program::factory()->published()->create(['slug' => 'binned-plan']);
    $exercise = Exercise::factory()->create(['slug' => 'binned-lift']);

    $program->delete();
    $exercise->delete();

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertDontSee('binned-plan')
        ->assertDontSee('binned-lift');
});

it('is well-formed XML', function (): void {
    Program::factory()->published()->create();

    $body = $this->get('/sitemap.xml')->assertOk()->getContent();

    $previous = libxml_use_internal_errors(true);
    $document = simplexml_load_string($body);
    libxml_use_internal_errors($previous);

    expect($document)->not->toBeFalse('the sitemap is not parseable XML');
    expect($document->getName())->toBe('urlset');
});
