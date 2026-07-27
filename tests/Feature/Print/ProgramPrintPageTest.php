<?php

declare(strict_types=1);

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Program;
use App\Models\ProgramDay;
use App\Models\ProgramExercise;

/**
 * A published, two-day program: one training day with a named exercise (so
 * the table has something real to check), one rest day (so DESIGN.md §8's
 * "a rest day prints as a rest day, never an empty table" has something to
 * exercise).
 */
function printableProgram(): Program
{
    $program = Program::factory()->create([
        'is_public' => true,
        'published_at' => now()->subDay(),
        'access_code' => null,
    ]);

    $muscle = MuscleGroup::factory()->create();
    $exercise = Exercise::factory()->create([
        'muscle_group_id' => $muscle->getKey(),
        'name_ar' => 'ضغط بنش تجريبي',
        'name_en' => 'Test Bench Press',
    ]);

    $trainingDay = ProgramDay::factory()->forProgram($program)->dayNumber(1)->create();
    ProgramExercise::factory()->forDay($trainingDay)->forExercise($exercise)->create([
        'sets' => 4,
        'reps' => '8-10',
        'rest_seconds' => 90,
    ]);

    ProgramDay::factory()->forProgram($program)->dayNumber(2)->rest()->create();

    return $program;
}

it('shows the club identity, the exercise name and the blank weight column', function (): void {
    $program = printableProgram();

    $response = $this->get('/programs/'.$program->slug.'/print');

    $response->assertOk();
    $response->assertSee('ضغط بنش تجريبي');
    $response->assertSee(__('program.print.weight_used'));
    $response->assertSee(__('exercise.prescription.sets'));
    $response->assertSee(__('exercise.prescription.reps'));
    $response->assertSee(__('exercise.prescription.rest'));
});

it('gives every day its own page, in order', function (): void {
    $program = printableProgram();

    $response = $this->get('/programs/'.$program->slug.'/print');

    // One <section class="day-page"> per day, and the CSS rule that starts
    // each one on a fresh sheet (DESIGN.md §8's break-before: page) shipped
    // alongside it.
    $response->assertSee('break-before: page', false);
    expect(substr_count($response->getContent(), 'class="day-page"'))->toBe(2);

    $response->assertSeeInOrder([
        __('program.days.number', ['number' => 1]),
        __('program.days.number', ['number' => 2]),
    ]);
});

it('marks a rest day clearly instead of an empty table', function (): void {
    $program = printableProgram();

    $this->get('/programs/'.$program->slug.'/print')
        ->assertSee(__('program.days.rest_title'))
        ->assertSee(__('program.days.rest_body'));
});

it('embeds a QR code linking back to the online program', function (): void {
    $program = printableProgram();

    $response = $this->get('/programs/'.$program->slug.'/print');

    $response->assertSee('<svg', false);
    $response->assertSee(route('programs.show', $program), false);
});

it('renders right-to-left in Arabic', function (): void {
    $program = printableProgram();

    $this->get('/programs/'.$program->slug.'/print')->assertSee('dir="rtl"', false);
});

it('renders left-to-right in English without touching a shared preference', function (): void {
    $program = printableProgram();

    $this->withSession(['locale' => 'en'])
        ->get('/programs/'.$program->slug.'/print')
        ->assertSee('dir="ltr"', false)
        ->assertSee('Test Bench Press');
});

it('prints a program with no days yet as an invitation, not a blank page', function (): void {
    $program = Program::factory()->create([
        'is_public' => true,
        'published_at' => now()->subDay(),
        'access_code' => null,
    ]);

    $this->get('/programs/'.$program->slug.'/print')
        ->assertOk()
        ->assertSee(__('program.days.none_title'));
});
