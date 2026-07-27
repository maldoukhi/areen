<?php

declare(strict_types=1);

use App\Models\Program;
use App\Models\ProgramDay;
use App\Models\ProgramExercise;
use App\Models\User;
use App\Models\WorkoutLog;

/*
 * An independent check of the two promises the offline queue rests on, written
 * against the endpoint rather than against the phase's own tests: a retried
 * batch must not duplicate a set, and one trainee must never be able to
 * overwrite another's by reusing their client_uuid.
 */

function traineeWithSet(): array
{
    $trainee = User::factory()->create(['role' => 'trainee', 'is_active' => true]);
    $program = Program::factory()->create(['is_public' => true, 'published_at' => now()->subDay()]);
    $day = ProgramDay::factory()->for($program)->create(['day_number' => 1]);
    $exercise = ProgramExercise::factory()->for($day, 'programDay')->create();

    $trainee->programs()->attach($program, ['started_at' => now()->subWeek(), 'is_active' => true]);

    return [$trainee, $exercise];
}

function batch(ProgramExercise $exercise, string $uuid, int $reps = 10, float $weight = 60.0): array
{
    return ['logs' => [[
        'client_uuid' => $uuid,
        'program_exercise_id' => $exercise->id,
        'performed_on' => now()->toDateString(),
        'set_number' => 1,
        'reps_done' => $reps,
        'weight' => $weight,
        'is_completed' => true,
    ]]];
}

it('does not duplicate a set when the same batch is sent five times', function (): void {
    [$trainee, $exercise] = traineeWithSet();
    $uuid = (string) Str::uuid();

    // A phone with no signal retries. It must not cost the trainee five rows.
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->actingAs($trainee)
            ->postJson('/dashboard/workout-logs/sync', batch($exercise, $uuid))
            ->assertOk();
    }

    expect(WorkoutLog::where('client_uuid', $uuid)->count())->toBe(1)
        ->and(WorkoutLog::count())->toBe(1);
});

it('updates rather than duplicates when a corrected weight reuses the uuid', function (): void {
    [$trainee, $exercise] = traineeWithSet();
    $uuid = (string) Str::uuid();

    $this->actingAs($trainee)->postJson('/dashboard/workout-logs/sync', batch($exercise, $uuid, 10, 60.0))->assertOk();
    $first = WorkoutLog::where('client_uuid', $uuid)->firstOrFail();

    $this->actingAs($trainee)->postJson('/dashboard/workout-logs/sync', batch($exercise, $uuid, 8, 72.5))->assertOk();
    $second = WorkoutLog::where('client_uuid', $uuid)->firstOrFail();

    expect(WorkoutLog::count())->toBe(1)
        ->and($second->id)->toBe($first->id)
        ->and((float) $second->weight)->toBe(72.5)
        ->and($second->reps_done)->toBe(8);
});

it('will not let one trainee overwrite another trainee\'s round', function (): void {
    [$owner, $exercise] = traineeWithSet();
    $uuid = (string) Str::uuid();

    $this->actingAs($owner)->postJson('/dashboard/workout-logs/sync', batch($exercise, $uuid, 10, 60.0))->assertOk();

    $attacker = User::factory()->create(['role' => 'trainee', 'is_active' => true]);
    $this->actingAs($attacker)->postJson('/dashboard/workout-logs/sync', batch($exercise, $uuid, 1, 999.0));

    $row = WorkoutLog::where('client_uuid', $uuid)->firstOrFail();

    expect($row->user_id)->toBe($owner->id)
        ->and((float) $row->weight)->toBe(60.0)
        ->and($row->reps_done)->toBe(10)
        ->and(WorkoutLog::where('user_id', $attacker->id)->count())->toBe(0);
});

it('turns a guest away from the sync endpoint', function (): void {
    [, $exercise] = traineeWithSet();

    $response = $this->postJson('/dashboard/workout-logs/sync', batch($exercise, (string) Str::uuid()));

    expect($response->status())->not->toBe(200)
        ->and(WorkoutLog::count())->toBe(0);
});
