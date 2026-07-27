<?php

declare(strict_types=1);

use App\Models\Program;
use App\Models\ProgramDay;

/*
 * The regression this file exists for: EnsureProgramIsViewable already guards
 * `programs.show` and `programs.day` (see tests/Feature/PrivateProgramAccessTest.php)
 * because a private program's schedule must have exactly one door — its access
 * code. P6 adds two more URLs onto that same schedule; if either forgot the
 * guard, the code would be pointless.
 */
function printProtectedProgram(): Program
{
    $program = Program::factory()->create([
        'is_public' => false,
        'published_at' => null,
        'access_code' => 'PRINTCODE',
    ]);

    ProgramDay::factory()->for($program)->create(['day_number' => 1]);

    return $program;
}

it('refuses to print a private program to someone who guessed the slug', function (): void {
    $this->get('/programs/'.printProtectedProgram()->slug.'/print')->assertNotFound();
});

it('refuses the pdf of a private program to someone who guessed the slug', function (): void {
    $this->get('/programs/'.printProtectedProgram()->slug.'/pdf')->assertNotFound();
});

it('opens the print page once the session has unlocked the program through its access code', function (): void {
    $program = printProtectedProgram();

    $this->get('/p/PRINTCODE')->assertOk();
    $this->get('/programs/'.$program->slug.'/print')->assertOk();
});

it('opens the pdf once the session has unlocked the program through its access code', function (): void {
    $program = printProtectedProgram();

    $this->get('/p/PRINTCODE')->assertOk();

    $response = $this->get('/programs/'.$program->slug.'/pdf');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
});

it('does not let one unlocked private program print a different one', function (): void {
    $first = printProtectedProgram();

    $second = Program::factory()->create([
        'is_public' => false,
        'published_at' => null,
        'access_code' => 'OTHERPRINT',
    ]);
    ProgramDay::factory()->for($second)->create(['day_number' => 1]);

    $this->get('/p/PRINTCODE')->assertOk();

    $this->get('/programs/'.$first->slug.'/print')->assertOk();
    $this->get('/programs/'.$second->slug.'/print')->assertNotFound();
});

it('prints and serves the pdf of a published program to anyone', function (): void {
    $program = Program::factory()->create([
        'is_public' => true,
        'published_at' => now()->subDay(),
        'access_code' => null,
    ]);
    ProgramDay::factory()->for($program)->create(['day_number' => 1]);

    $this->get('/programs/'.$program->slug.'/print')->assertOk();
    $this->get('/programs/'.$program->slug.'/pdf')->assertOk();
});

it('keeps a program scheduled for the future unprintable', function (): void {
    $program = Program::factory()->create([
        'is_public' => true,
        'published_at' => now()->addWeek(),
        'access_code' => null,
    ]);
    ProgramDay::factory()->for($program)->create(['day_number' => 1]);

    $this->get('/programs/'.$program->slug.'/print')->assertNotFound();
    $this->get('/programs/'.$program->slug.'/pdf')->assertNotFound();
});
