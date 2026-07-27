<?php

declare(strict_types=1);

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Program;
use App\Models\ProgramDay;

/*
 * The whole public surface, walked as a signed-out visitor. Every earlier phase
 * tested the page it added; this is the pass that asks whether the site as a
 * whole still answers.
 */

it('answers every public route for a visitor with no account', function (string $path): void {
    $muscle = MuscleGroup::factory()->named('chest')->create();
    Exercise::factory()->for($muscle)->create(['slug' => 'guest-press']);

    $program = Program::factory()->published()->create(['slug' => 'guest-plan', 'days_count' => 1]);
    ProgramDay::factory()->for($program)->create(['day_number' => 1]);

    Program::factory()->create([
        'slug' => 'guest-private',
        'is_public' => false,
        'published_at' => null,
        'access_code' => 'GUESTKEY',
    ]);

    $this->assertGuest();

    $this->get($path)->assertOk();
})->with([
    '/',
    '/programs',
    '/programs/guest-plan',
    '/programs/guest-plan/day/1',
    '/exercises',
    '/exercises/guest-press',
    '/muscles/chest',
    '/p/GUESTKEY',
    '/about',
    '/offline',
    '/manifest.json',
    '/robots.txt',
    '/sitemap.xml',
    '/og-image.png',
    '/up',
]);

/*
 * A program can exist before its days do — the admin creates the shell first —
 * and the overview has to read as unfinished rather than broken.
 */
it('shows a program with no days at all without falling over', function (): void {
    Program::factory()->published()->create(['slug' => 'empty-plan', 'days_count' => 0]);

    $this->get('/programs/empty-plan')->assertOk();

    // There is no day 1 to open, so the day route is a 404 and not a 500.
    $this->get('/programs/empty-plan/day/1')->assertNotFound();
});

it('shows a day with no exercises as an invitation, not as a crash', function (): void {
    $program = Program::factory()->published()->create(['slug' => 'bare-plan', 'days_count' => 1]);

    ProgramDay::factory()->for($program)->create(['day_number' => 1, 'is_rest_day' => false]);

    $this->get('/programs/bare-plan/day/1')
        ->assertOk()
        ->assertSee(__('program.days.empty_title'))
        // The fixed log bar has nothing to log, so it is not drawn at all.
        ->assertDontSee(__('program.days.log_set'));
});

it('reads a rest day as a plan rather than as an empty state', function (): void {
    $program = Program::factory()->published()->create(['slug' => 'rest-plan', 'days_count' => 1]);

    ProgramDay::factory()->for($program)->create(['day_number' => 1, 'is_rest_day' => true]);

    $this->get('/programs/rest-plan/day/1')
        ->assertOk()
        ->assertSee(__('program.days.rest_title'))
        ->assertDontSee(__('program.days.empty_title'));
});

it('refuses a day number the program does not have', function (): void {
    $program = Program::factory()->published()->create(['slug' => 'short-plan', 'days_count' => 1]);

    ProgramDay::factory()->for($program)->create(['day_number' => 1]);

    $this->get('/programs/short-plan/day/99')->assertNotFound();
    $this->get('/programs/short-plan/day/0')->assertNotFound();
    $this->get('/programs/short-plan/day/abc')->assertNotFound();
});

/*
 * A filter combination with nothing behind it is the state a coach reaches by
 * accident, and the one that used to render an unexplained blank grid.
 */
it('explains an empty library rather than showing a blank grid', function (): void {
    $chest = MuscleGroup::factory()->named('chest')->create();
    $back = MuscleGroup::factory()->named('back')->create();

    // Chest has only a barbell exercise; back has only a machine one. So
    // "chest + machine" is a combination that exists in the bar and matches
    // nothing on the shelf.
    Exercise::factory()->for($chest)->create(['slug' => 'combo-bench', 'equipment' => 'barbell']);
    Exercise::factory()->for($back)->create(['slug' => 'combo-row', 'equipment' => 'machine']);

    $this->get('/exercises?muscle=chest&equipment=machine')
        ->assertOk()
        ->assertSee(__('exercise.filters.none_title'))
        ->assertDontSee('combo-bench')
        ->assertDontSee('combo-row');
});

it('names the single filter that emptied the shelf when one is to blame', function (): void {
    $chest = MuscleGroup::factory()->named('chest')->create();
    $back = MuscleGroup::factory()->named('back')->create();

    Exercise::factory()->for($chest)->create(['slug' => 'blame-bench', 'equipment' => 'barbell']);
    // Cable has to exist somewhere on the shelf, or the bar would never offer it
    // and the page would quietly drop the filter instead of reporting it.
    Exercise::factory()->for($back)->create(['slug' => 'blame-pulldown', 'equipment' => 'cable']);

    // Lifting the equipment filter alone brings chest back, so it is the culprit
    // and the empty state says which control to undo.
    $this->get('/exercises?muscle=chest&equipment=cable')
        ->assertOk()
        ->assertSee(__('exercise.filters.none_title'))
        ->assertSee(__('exercise.filters.none_for', [
            'filter' => __('exercise.equipment.label'),
            'value' => __('exercise.equipment.cable'),
        ]));
});

it('degrades a stale filter in a shared link to the whole library', function (): void {
    $chest = MuscleGroup::factory()->named('chest')->create();
    Exercise::factory()->for($chest)->create(['slug' => 'stale-bench']);

    // The muscle group in this link no longer exists.
    $this->get('/exercises?muscle=a-group-that-was-deleted')
        ->assertOk()
        ->assertSee('stale-bench');
});

it('keeps a private program out of the catalogue and off its slug', function (): void {
    $private = Program::factory()->create([
        'slug' => 'surface-private',
        'is_public' => false,
        'published_at' => null,
        'access_code' => 'SURFACE1',
    ]);

    $this->get('/programs')->assertOk()->assertDontSee($private->name);
    $this->get('/programs/surface-private')->assertNotFound();
    $this->get('/programs/surface-private/day/1')->assertNotFound();

    // The coded door is the only way in, and it works.
    $this->get('/p/SURFACE1')->assertOk()->assertSee($private->name);
});
