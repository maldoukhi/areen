<?php

declare(strict_types=1);

use App\Actions\Trainee\ResolveTrainingPlan;
use App\Models\BodyMetric;
use App\Models\Exercise;
use App\Models\Program;
use App\Models\ProgramDay;
use App\Models\ProgramExercise;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Support\Str;

/**
 * A trainee on a two-day program whose first day carries one named exercise.
 *
 * @return array{user: User, program: Program, day: ProgramDay, exercise: ProgramExercise}
 */
function traineeOnProgram(string $exerciseName = 'Barbell Back Squat'): array
{
    $user = User::factory()->trainee()->create();
    $program = Program::factory()->create();

    // Untitled on purpose: `program_days.title_*` is nullable (CLAUDE.md §4) and
    // the screens have to read as sentences without it.
    $day = ProgramDay::factory()->forProgram($program)->dayNumber(1)->create([
        'title_ar' => null,
        'title_en' => null,
    ]);

    ProgramDay::factory()->forProgram($program)->dayNumber(2)->create([
        'title_ar' => null,
        'title_en' => null,
    ]);

    $exercise = Exercise::factory()->create([
        'name_ar' => $exerciseName,
        'name_en' => $exerciseName,
    ]);

    $prescribed = ProgramExercise::factory()
        ->forDay($day)
        ->forExercise($exercise)
        ->create(['sets' => 3, 'reps' => '8-10', 'rest_seconds' => 90]);

    $program->users()->attach($user, [
        'started_at' => now()->toDateString(),
        'is_active' => true,
    ]);

    return ['user' => $user, 'program' => $program, 'day' => $day, 'exercise' => $prescribed];
}

it('sends a guest to the one sign-in door', function (): void {
    $this->get('/dashboard')->assertRedirect(route('admin.login'));
    $this->get('/dashboard/log')->assertRedirect(route('admin.login'));
    $this->get('/dashboard/progress')->assertRedirect(route('admin.login'));
});

it('opens the dashboard on the program the trainee is following', function (): void {
    ['user' => $user, 'program' => $program] = traineeOnProgram();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee($program->name)
        ->assertSee(__('program.days.number', ['number' => 1]))
        ->assertSee(__('trainee.dashboard.start_session'));
});

it('invites a trainee with no program instead of showing an empty screen', function (): void {
    $user = User::factory()->trainee()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee(__('trainee.dashboard.empty_title'))
        ->assertDontSee(__('trainee.dashboard.start_session'));
});

it('points at the day after the last one logged', function (): void {
    ['user' => $user, 'exercise' => $exercise] = traineeOnProgram();

    WorkoutLog::factory()->forUser($user)->forProgramExercise($exercise)->create([
        'performed_on' => now()->subDays(2)->toDateString(),
        'set_number' => 1,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee(__('trainee.dashboard.next_up'))
        ->assertSee(__('program.days.number', ['number' => 2]));
});

it('counts a streak that is still alive from yesterday', function (): void {
    ['user' => $user, 'exercise' => $exercise] = traineeOnProgram();

    foreach ([1, 2, 3] as $daysAgo) {
        WorkoutLog::factory()->forUser($user)->forProgramExercise($exercise)->create([
            'performed_on' => now()->subDays($daysAgo)->toDateString(),
            'set_number' => $daysAgo,
        ]);
    }

    $plan = app(ResolveTrainingPlan::class)->handle($user->fresh());

    expect($plan['streak'])->toBe(3)
        ->and($plan['sets_this_week'])->toBe(3);
});

it('lists a set row for every prescribed round of the day', function (): void {
    ['user' => $user, 'program' => $program, 'exercise' => $exercise] = traineeOnProgram();

    $response = $this->actingAs($user)
        ->get('/dashboard/log?program='.$program->slug.'&day=1')
        ->assertOk()
        ->assertSee(__('trainee.log.save'));

    // Counted by a rendered attribute value: the runtime inlined on the same page
    // mentions `data-set-row` too, so a bare substring count would find both.
    preg_match_all('/data-set-number="\d+"/', $response->getContent(), $matches);

    expect($matches[0])->toHaveCount(3)
        ->and($response->getContent())->toContain('data-program-exercise="'.$exercise->getKey().'"');
});

it('paints a round already logged today, carrying its client uuid', function (): void {
    ['user' => $user, 'program' => $program, 'exercise' => $exercise] = traineeOnProgram();

    $uuid = (string) Str::uuid();

    WorkoutLog::factory()->forUser($user)->forProgramExercise($exercise)->pendingSync()->create([
        'performed_on' => now()->toDateString(),
        'set_number' => 2,
        'reps_done' => 9,
        'weight' => 62.5,
        'client_uuid' => $uuid,
    ]);

    $response = $this->actingAs($user)
        ->get('/dashboard/log?program='.$program->slug.'&day=1')
        ->assertOk();

    expect($response->getContent())->toContain('data-uuid="'.$uuid.'"')
        // Not yet acknowledged by the server, so it still wears the ember dot.
        ->toContain('data-sync="pending"');
});

it('will not open a program that is not on the trainee\'s account', function (): void {
    ['user' => $user] = traineeOnProgram();

    $strangers = Program::factory()->create();
    $day = ProgramDay::factory()->forProgram($strangers)->dayNumber(1)->create();
    $secret = Exercise::factory()->create(['name_ar' => 'Secret Lift', 'name_en' => 'Secret Lift']);
    ProgramExercise::factory()->forDay($day)->forExercise($secret)->create(['sets' => 4]);

    $this->actingAs($user)
        ->get('/dashboard/log?program='.$strangers->slug.'&day=1')
        ->assertOk()
        ->assertDontSee('Secret Lift');
});

it('survives a day number that is not a number', function (): void {
    ['user' => $user, 'program' => $program] = traineeOnProgram();

    $this->actingAs($user)
        ->get('/dashboard/log?program='.$program->slug.'&day=not-a-day')
        ->assertOk();
});

it('turns a suspended account away from the trainee screens', function (): void {
    $user = User::factory()->trainee()->inactive()->create();

    $this->actingAs($user)->get('/dashboard')->assertForbidden();
    $this->actingAs($user)->get('/dashboard/progress')->assertForbidden();
});

it('draws the progress chart from the rounds already logged', function (): void {
    ['user' => $user, 'exercise' => $exercise] = traineeOnProgram('Front Squat');

    foreach ([10, 7, 3] as $daysAgo) {
        WorkoutLog::factory()->forUser($user)->forProgramExercise($exercise)->create([
            'performed_on' => now()->subDays($daysAgo)->toDateString(),
            'set_number' => 1,
            'reps_done' => 8,
            'weight' => 60 + $daysAgo,
        ]);
    }

    $response = $this->actingAs($user)
        ->get('/dashboard/progress')
        ->assertOk()
        ->assertSee('Front Squat')
        ->assertSee(__('trainee.progress.chart_weight'));

    // Inline SVG, no charting library anywhere near it.
    expect($response->getContent())->toContain('<polyline');
});

it('invites a first measurement and then records one', function (): void {
    ['user' => $user] = traineeOnProgram();

    $this->actingAs($user)
        ->get('/dashboard/progress')
        ->assertOk()
        ->assertSee(__('trainee.metrics.empty_title'));

    $this->actingAs($user)
        ->from('/dashboard/progress')
        ->post(route('dashboard.metrics.store'), [
            'measured_on' => now()->toDateString(),
            'weight' => 82.4,
            'body_fat' => 20.5,
            'notes' => null,
        ])
        ->assertRedirect('/dashboard/progress');

    expect(BodyMetric::query()->where('user_id', $user->getKey())->count())->toBe(1);
});

it('corrects the same day rather than colliding with the unique index', function (): void {
    ['user' => $user] = traineeOnProgram();

    $payload = ['measured_on' => now()->toDateString(), 'weight' => 82.4];

    $this->actingAs($user)->from('/dashboard/progress')->post(route('dashboard.metrics.store'), $payload);
    $this->actingAs($user)->from('/dashboard/progress')->post(route('dashboard.metrics.store'), [
        'measured_on' => $payload['measured_on'],
        'weight' => 81.9,
    ]);

    $metrics = BodyMetric::query()->where('user_id', $user->getKey())->get();

    expect($metrics)->toHaveCount(1)
        ->and((float) $metrics->first()->weight)->toBe(81.9);
});

it('refuses a measurement with neither weight nor body fat', function (): void {
    ['user' => $user] = traineeOnProgram();

    $this->actingAs($user)
        ->from('/dashboard/progress')
        ->post(route('dashboard.metrics.store'), ['measured_on' => now()->toDateString()])
        ->assertSessionHasErrors('weight');

    expect(BodyMetric::query()->count())->toBe(0);
});

it('never shows one trainee another trainee\'s measurements', function (): void {
    ['user' => $user] = traineeOnProgram();

    $other = User::factory()->trainee()->create();
    BodyMetric::factory()->forUser($other)->create(['weight' => 123.45]);

    $this->actingAs($user)
        ->get('/dashboard/progress')
        ->assertOk()
        ->assertDontSee('123.4');
});
