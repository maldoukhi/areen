<?php

declare(strict_types=1);

use App\Models\Program;
use App\Models\ProgramDay;
use App\Models\ProgramExercise;
use App\Models\User;

/*
 * The journey «سجّل الجولة» offers used to be a loop: the day page invited any
 * signed-in visitor to log, the logging screen only resolved a programme the
 * trainee was enrolled in, and its empty state sent them to a dashboard that
 * sent them to the catalogue that sent them back to the same day. These tests
 * hold the exits open.
 */

function publicProgram(): Program
{
    $program = Program::factory()->create(['is_public' => true, 'published_at' => now()->subDay()]);
    $day = ProgramDay::factory()->for($program)->create(['day_number' => 1]);
    ProgramExercise::factory()->for($day, 'programDay')->create();

    return $program;
}

function trainee(): User
{
    return User::factory()->create(['role' => 'trainee', 'is_active' => true]);
}

it('offers joining, not logging, when the plan is not yet the visitor\'s', function (): void {
    $program = publicProgram();

    $html = $this->actingAs(trainee())
        ->get('/programs/'.$program->slug.'/day/1')
        ->assertOk()
        ->getContent();

    expect($html)->toContain(__('program.actions.start'))
        // The query separator is escaped in markup, so match the path instead.
        ->and($html)->not->toContain('/dashboard/log?program='.$program->slug);
});

it('offers logging once the plan is theirs', function (): void {
    $program = publicProgram();
    $trainee = trainee();
    $trainee->programs()->attach($program, ['started_at' => now(), 'is_active' => true]);

    $html = $this->actingAs($trainee)
        ->get('/programs/'.$program->slug.'/day/1')
        ->assertOk()
        ->getContent();

    expect($html)->toContain('/dashboard/log?program='.$program->slug)
        ->and($html)->toContain(__('program.days.log_set'));
});

it('enrols the trainee and lands them on a logging screen that works', function (): void {
    $program = publicProgram();
    $trainee = trainee();

    // actingAs must precede test(): mount runs immediately and reads the user.
    $this->actingAs($trainee);

    Livewire::test('pages::programs.day', ['program' => $program, 'day' => 1])
        ->call('startProgram')
        ->assertRedirect(route('dashboard.log', ['program' => $program->slug, 'day' => 1]));

    expect($trainee->programs()->whereKey($program->getKey())->exists())->toBeTrue();

    // The destination now resolves a real day rather than an empty state.
    $this->actingAs($trainee)
        ->get('/dashboard/log?program='.$program->slug.'&day=1')
        ->assertOk()
        ->assertDontSee(__('trainee.log.no_day_title'));
});

it('names the real problem instead of dead-ending', function (): void {
    $program = publicProgram();

    $this->actingAs(trainee())
        ->get('/dashboard/log?program='.$program->slug.'&day=1')
        ->assertOk()
        ->assertSee(__('trainee.log.not_enrolled_title'))
        // The way out points back at the programme, never at the dashboard.
        ->assertSee('/programs/'.$program->slug, escape: false);
});

it('will not let joining reach a programme the visitor cannot open', function (): void {
    $private = Program::factory()->create([
        'is_public' => false, 'published_at' => null, 'access_code' => 'SECRET01',
    ]);
    ProgramDay::factory()->for($private)->create(['day_number' => 1]);

    $this->actingAs(trainee())
        ->get('/programs/'.$private->slug.'/day/1')
        ->assertNotFound();
});
