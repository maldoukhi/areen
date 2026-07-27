<?php

declare(strict_types=1);

use App\Models\Program;
use App\Models\ProgramDay;

function privateProgram(): Program
{
    $program = Program::factory()->create([
        'is_public' => false,
        'published_at' => null,
        'access_code' => 'TESTCODE',
    ]);

    ProgramDay::factory()->for($program)->create(['day_number' => 1]);

    return $program;
}

it('hides a private program from the public catalogue', function (): void {
    $program = privateProgram();

    $this->get('/programs')->assertOk()->assertDontSee($program->name_ar);
});

it('refuses the overview of a private program to someone who guessed the slug', function (): void {
    $this->get('/programs/'.privateProgram()->slug)->assertNotFound();
});

/*
 * The regression this file exists for: gating only the overview left the whole
 * schedule readable one URL deeper, which defeated the access code entirely.
 */
it('refuses a private program day to someone who guessed the slug', function (): void {
    $this->get('/programs/'.privateProgram()->slug.'/day/1')->assertNotFound();
});

it('opens the program to a visitor who arrives through the access code', function (): void {
    $program = privateProgram();

    $this->get('/p/TESTCODE')->assertOk();
    $this->get('/programs/'.$program->slug.'/day/1')->assertOk();
});

it('rejects an unknown access code', function (): void {
    privateProgram();

    $this->get('/p/WRONGCODE')->assertNotFound();
});

it('does not let an unlocked program open a different private program', function (): void {
    $first = privateProgram();

    $second = Program::factory()->create([
        'is_public' => false,
        'published_at' => null,
        'access_code' => 'OTHERCODE',
    ]);
    ProgramDay::factory()->for($second)->create(['day_number' => 1]);

    $this->get('/p/TESTCODE')->assertOk();

    $this->get('/programs/'.$first->slug.'/day/1')->assertOk();
    $this->get('/programs/'.$second->slug.'/day/1')->assertNotFound();
});

it('keeps a published program open to everyone', function (): void {
    $program = Program::factory()->create([
        'is_public' => true,
        'published_at' => now()->subDay(),
        'access_code' => null,
    ]);
    ProgramDay::factory()->for($program)->create(['day_number' => 1]);

    $this->get('/programs/'.$program->slug)->assertOk();
    $this->get('/programs/'.$program->slug.'/day/1')->assertOk();
});

it('keeps a program scheduled for the future out of reach', function (): void {
    $program = Program::factory()->create([
        'is_public' => true,
        'published_at' => now()->addWeek(),
        'access_code' => null,
    ]);

    $this->get('/programs/'.$program->slug)->assertNotFound();
});
