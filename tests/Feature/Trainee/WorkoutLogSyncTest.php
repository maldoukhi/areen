<?php

declare(strict_types=1);

use App\Models\Program;
use App\Models\ProgramDay;
use App\Models\ProgramExercise;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Support\Str;

/**
 * A trainee with one program attached and one prescribed exercise inside it.
 *
 * @return array{0: User, 1: ProgramExercise, 2: Program}
 */
function syncFixture(?User $user = null): array
{
    $user ??= User::factory()->trainee()->create();

    $program = Program::factory()->create();
    $day = ProgramDay::factory()->forProgram($program)->dayNumber(1)->create();
    $exercise = ProgramExercise::factory()->forDay($day)->create(['sets' => 3]);

    $program->users()->attach($user, [
        'started_at' => now()->toDateString(),
        'is_active' => true,
    ]);

    return [$user, $exercise, $program];
}

/**
 * @param  list<string>  $uuids
 * @return list<array<string, mixed>>
 */
function roundsFor(ProgramExercise $exercise, array $uuids): array
{
    $rounds = [];

    foreach (array_values($uuids) as $index => $uuid) {
        $rounds[] = [
            'client_uuid' => $uuid,
            'program_exercise_id' => $exercise->getKey(),
            'performed_on' => now()->toDateString(),
            'set_number' => $index + 1,
            'reps_done' => 10 - $index,
            'weight' => 60 + ($index * 2.5),
            'is_completed' => true,
            'note' => null,
        ];
    }

    return $rounds;
}

/*
 | `bootstrap/app.php` narrows JSON rendering to `api/*`, so an unauthenticated
 | call here is redirected to the one sign-in door rather than answered with 401.
 | The queue survives either shape: the redirect lands on HTML, `response.json()`
 | throws, the drain counts it as a failure and the rounds stay on the phone until
 | the trainee signs in again.
 */
it('turns a guest away from the sync endpoint', function (): void {
    $this->postJson(route('dashboard.logs.sync'), ['logs' => []])
        ->assertRedirect(route('admin.login'));

    expect(WorkoutLog::query()->count())->toBe(0);
});

it('lands a batch of offline rounds and stamps them synced', function (): void {
    [$user, $exercise] = syncFixture();

    $rounds = roundsFor($exercise, [(string) Str::uuid(), (string) Str::uuid(), (string) Str::uuid()]);

    $response = $this->actingAs($user)
        ->postJson(route('dashboard.logs.sync'), ['logs' => $rounds])
        ->assertOk();

    expect($response->json('accepted'))->toHaveCount(3)
        ->and($response->json('rejected'))->toBe([]);

    $stored = WorkoutLog::query()->where('user_id', $user->getKey())->get();

    expect($stored)->toHaveCount(3)
        ->and($stored->every(fn (WorkoutLog $log): bool => $log->isSynced()))->toBeTrue()
        ->and((float) $stored->firstWhere('set_number', 1)->weight)->toBe(60.0);
});

/*
 | The one behaviour the whole offline design rests on. Background Sync retries,
 | the `online` event fires, the page is reopened — the same batch reaches the
 | server more than once by design, and must land exactly once.
 */
it('writes one row when the identical batch arrives twice', function (): void {
    [$user, $exercise] = syncFixture();

    $uuids = [(string) Str::uuid(), (string) Str::uuid(), (string) Str::uuid()];
    $rounds = roundsFor($exercise, $uuids);

    $this->actingAs($user)->postJson(route('dashboard.logs.sync'), ['logs' => $rounds])->assertOk();

    $countAfterFirst = WorkoutLog::query()->count();
    $idsAfterFirst = WorkoutLog::query()->orderBy('id')->pluck('id')->all();

    $second = $this->actingAs($user)
        ->postJson(route('dashboard.logs.sync'), ['logs' => $rounds])
        ->assertOk();

    expect($second->json('accepted'))->toHaveCount(3);

    $countAfterSecond = WorkoutLog::query()->count();
    $storedUuids = WorkoutLog::query()->pluck('client_uuid')->all();

    expect($countAfterSecond)->toBe($countAfterFirst)
        ->and($countAfterSecond)->toBe(3)
        // Same rows, not replacements: no delete-and-reinsert behind our back.
        ->and(WorkoutLog::query()->orderBy('id')->pluck('id')->all())->toBe($idsAfterFirst)
        // `client_uuid` is unique in the schema and stayed that way.
        ->and($storedUuids)->toHaveCount(count(array_unique($storedUuids)))
        ->and(array_values(array_diff($uuids, $storedUuids)))->toBe([]);
});

it('corrects a round rather than duplicating it when the figures change', function (): void {
    [$user, $exercise] = syncFixture();

    $uuid = (string) Str::uuid();
    $round = roundsFor($exercise, [$uuid])[0];

    $this->actingAs($user)->postJson(route('dashboard.logs.sync'), ['logs' => [$round]])->assertOk();

    $round['weight'] = 72.5;
    $round['reps_done'] = 8;

    $this->actingAs($user)->postJson(route('dashboard.logs.sync'), ['logs' => [$round]])->assertOk();

    $stored = WorkoutLog::query()->where('client_uuid', $uuid)->get();

    expect($stored)->toHaveCount(1)
        ->and((float) $stored->first()->weight)->toBe(72.5)
        ->and($stored->first()->reps_done)->toBe(8);
});

/*
 | A `client_uuid` is generated on the phone, so it is guessable in principle.
 | It must never be a key to somebody else's history.
 */
it('refuses to let one trainee overwrite another trainee\'s round', function (): void {
    [$owner, $ownerExercise] = syncFixture();
    [$attacker, $attackerExercise] = syncFixture();

    $uuid = (string) Str::uuid();

    $this->actingAs($owner)
        ->postJson(route('dashboard.logs.sync'), ['logs' => roundsFor($ownerExercise, [$uuid])])
        ->assertOk();

    $before = WorkoutLog::query()->where('client_uuid', $uuid)->firstOrFail();

    $forged = roundsFor($attackerExercise, [$uuid])[0];
    $forged['reps_done'] = 999;
    $forged['weight'] = 1.5;

    $response = $this->actingAs($attacker)
        ->postJson(route('dashboard.logs.sync'), ['logs' => [$forged]])
        ->assertOk();

    expect($response->json('accepted'))->toBe([])
        ->and($response->json('rejected.0.reason'))->toBe('owned_by_another');

    $after = WorkoutLog::query()->where('client_uuid', $uuid)->firstOrFail();

    expect(WorkoutLog::query()->where('client_uuid', $uuid)->count())->toBe(1)
        ->and($after->user_id)->toBe($owner->getKey())
        ->and($after->reps_done)->toBe($before->reps_done)
        ->and((float) $after->weight)->toBe((float) $before->weight)
        ->and(WorkoutLog::query()->where('user_id', $attacker->getKey())->count())->toBe(0);
});

it('refuses a round against a program the trainee is not on', function (): void {
    [$user] = syncFixture();

    // A second program, never attached to this account.
    $other = Program::factory()->create();
    $day = ProgramDay::factory()->forProgram($other)->dayNumber(1)->create();
    $strangersExercise = ProgramExercise::factory()->forDay($day)->create(['sets' => 3]);

    $response = $this->actingAs($user)
        ->postJson(route('dashboard.logs.sync'), ['logs' => roundsFor($strangersExercise, [(string) Str::uuid()])])
        ->assertOk();

    expect($response->json('accepted'))->toBe([])
        ->and($response->json('rejected.0.reason'))->toBe('forbidden')
        ->and(WorkoutLog::query()->count())->toBe(0);
});

it('names the offending round when the payload is malformed', function (): void {
    [$user, $exercise] = syncFixture();

    $rounds = roundsFor($exercise, [(string) Str::uuid(), (string) Str::uuid()]);
    $rounds[1]['set_number'] = 0;

    $this->actingAs($user)
        ->postJson(route('dashboard.logs.sync'), ['logs' => $rounds])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('logs.1.set_number');

    expect(WorkoutLog::query()->count())->toBe(0);
});

it('refuses an unknown prescribed exercise outright', function (): void {
    [$user, $exercise] = syncFixture();

    $round = roundsFor($exercise, [(string) Str::uuid()])[0];
    $round['program_exercise_id'] = 999999;

    $this->actingAs($user)
        ->postJson(route('dashboard.logs.sync'), ['logs' => [$round]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('logs.0.program_exercise_id');
});

it('refuses a suspended account', function (): void {
    [$user, $exercise] = syncFixture(User::factory()->trainee()->inactive()->create());

    $this->actingAs($user)
        ->postJson(route('dashboard.logs.sync'), ['logs' => roundsFor($exercise, [(string) Str::uuid()])])
        ->assertForbidden();

    expect(WorkoutLog::query()->count())->toBe(0);
});
