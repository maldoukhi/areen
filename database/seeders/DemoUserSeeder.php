<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\BodyMetric;
use App\Models\Program;
use App\Models\ProgramExercise;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo accounts and a trainee with real history behind him, so the dashboard and
 * the progress chart have something to draw.
 *
 * Local and testing only — DatabaseSeeder guards the call. Never run this on a
 * club's production database.
 */
class DemoUserSeeder extends Seeder
{
    private const PASSWORD = 'password';

    /**
     * @var list<array{name: string, email: string, role: string, phone: string}>
     */
    private const USERS = [
        ['name' => 'أحمد العنزي', 'email' => 'admin@example.test', 'role' => UserRole::Admin->value, 'phone' => '+966500000001'],
        ['name' => 'عبدالله الحربي', 'email' => 'coach@example.test', 'role' => UserRole::Coach->value, 'phone' => '+966500000002'],
        ['name' => 'خالد الشمري', 'email' => 'khalid@example.test', 'role' => UserRole::Trainee->value, 'phone' => '+966500000003'],
        ['name' => 'فيصل الدوسري', 'email' => 'faisal@example.test', 'role' => UserRole::Trainee->value, 'phone' => '+966500000004'],
        ['name' => 'سلطان القحطاني', 'email' => 'sultan@example.test', 'role' => UserRole::Trainee->value, 'phone' => '+966500000005'],
    ];

    /**
     * Starting load in kg and the weekly jump, per exercise slug. Anything not
     * listed is a bodyweight or timed movement and gets no load at all.
     *
     * @var array<string, array{0: float, 1: float}>
     */
    private const LOADS = [
        'barbell-back-squat' => [60.0, 5.0],
        'barbell-bench-press' => [45.0, 2.5],
        'seated-cable-row' => [40.0, 2.5],
        'seated-dumbbell-shoulder-press' => [14.0, 1.0],
        'romanian-deadlift' => [50.0, 5.0],
        'lat-pulldown' => [45.0, 2.5],
        'incline-dumbbell-press' => [16.0, 2.0],
        'dumbbell-lateral-raise' => [6.0, 0.5],
        'barbell-curl' => [20.0, 1.25],
        'cable-triceps-pushdown' => [25.0, 2.5],
        'leg-press' => [100.0, 10.0],
        'dumbbell-lunge' => [12.0, 2.0],
        'one-arm-dumbbell-row' => [22.0, 2.0],
        'cable-face-pull' => [20.0, 2.5],
        'standing-calf-raise' => [40.0, 5.0],
    ];

    /**
     * @var list<array{weeks_ago: int, weight: float, body_fat: float, notes: string|null}>
     */
    private const METRICS = [
        ['weeks_ago' => 4, 'weight' => 84.6, 'body_fat' => 22.4, 'notes' => 'قياس الصباح قبل الإفطار.'],
        ['weeks_ago' => 3, 'weight' => 84.0, 'body_fat' => 22.0, 'notes' => null],
        ['weeks_ago' => 2, 'weight' => 83.2, 'body_fat' => 21.5, 'notes' => 'التزمت بالسعرات ستة أيام من سبعة.'],
        ['weeks_ago' => 1, 'weight' => 82.7, 'body_fat' => 21.0, 'notes' => null],
        ['weeks_ago' => 0, 'weight' => 82.1, 'body_fat' => 20.6, 'notes' => 'الوزن نزل والأداء في السكوات تحسّن.'],
    ];

    public function run(): void
    {
        $users = $this->seedUsers();

        $beginner = Program::query()->where('slug', 'beginner-full-body-3-day')->first();
        $private = Program::query()->where('slug', 'private-cut-8-weeks')->first();

        $khalid = $users['khalid@example.test'];
        $faisal = $users['faisal@example.test'];

        if ($beginner instanceof Program) {
            $this->attachProgram($beginner, $khalid, now()->startOfDay()->subWeeks(3)->toDateString());
            $this->seedWorkoutHistory($beginner, $khalid);
        }

        if ($private instanceof Program) {
            $this->attachProgram($private, $faisal, now()->startOfDay()->subWeek()->toDateString());
        }

        $this->seedBodyMetrics($khalid);
    }

    /**
     * @return array<string, User>
     */
    private function seedUsers(): array
    {
        $password = Hash::make(self::PASSWORD);
        $users = [];

        foreach (self::USERS as $user) {
            $model = User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => $password,
                    'role' => $user['role'],
                    'locale' => 'ar',
                    'phone' => $user['phone'],
                    'is_active' => true,
                ]
            );

            // `email_verified_at` is deliberately not mass assignable on User.
            if ($model->email_verified_at === null) {
                $model->forceFill(['email_verified_at' => now()])->save();
            }

            $users[$user['email']] = $model;
        }

        return $users;
    }

    private function attachProgram(Program $program, User $user, string $startedAt): void
    {
        $program->users()->syncWithoutDetaching([
            $user->getKey() => [
                'started_at' => $startedAt,
                'is_active' => true,
            ],
        ]);
    }

    /**
     * Three weeks of logged rounds, three sessions a week, with the load creeping
     * up every week the way a real beginner's log looks.
     */
    private function seedWorkoutHistory(Program $program, User $user): void
    {
        $rows = $this->programExerciseRows($program);

        if ($rows->isEmpty()) {
            return;
        }

        $start = now()->startOfDay()->subWeeks(3);

        foreach (range(0, 2) as $week) {
            foreach ($rows->keys() as $dayIndex => $dayNumber) {
                $performedOn = $start->copy()->addDays($week * 7 + $dayIndex * 2);

                foreach ($rows[$dayNumber] as $row) {
                    $this->logSets($user, $row, $performedOn, $week);
                }
            }
        }
    }

    /**
     * @return Collection<int, Collection<int, object{id: int, sets: int, reps: string|null, slug: string}>>
     */
    private function programExerciseRows(Program $program): Collection
    {
        return ProgramExercise::query()
            ->join('program_days', 'program_days.id', '=', 'program_exercises.program_day_id')
            ->join('exercises', 'exercises.id', '=', 'program_exercises.exercise_id')
            ->where('program_days.program_id', $program->getKey())
            ->where('program_days.is_rest_day', false)
            ->orderBy('program_days.day_number')
            ->orderBy('program_exercises.sort')
            ->get([
                'program_exercises.id as id',
                'program_exercises.sets as sets',
                'program_exercises.reps as reps',
                'program_days.day_number as day_number',
                'exercises.slug as slug',
            ])
            ->groupBy('day_number');
    }

    private function logSets(User $user, object $row, Carbon $performedOn, int $week): void
    {
        [$base, $step] = self::LOADS[$row->slug] ?? [null, null];
        $target = $this->repTarget(is_string($row->reps) ? $row->reps : '');

        foreach (range(1, (int) $row->sets) as $setNumber) {
            // `performed_on` is a date cast, so the comparison goes through
            // whereDate rather than a raw string match — that keeps re-seeding
            // idempotent whatever the driver stores.
            $alreadyLogged = WorkoutLog::query()
                ->where('user_id', $user->getKey())
                ->where('program_exercise_id', $row->id)
                ->whereDate('performed_on', $performedOn->toDateString())
                ->where('set_number', $setNumber)
                ->exists();

            if ($alreadyLogged) {
                continue;
            }

            WorkoutLog::query()->create([
                'user_id' => $user->getKey(),
                'program_exercise_id' => $row->id,
                'performed_on' => $performedOn->toDateString(),
                'set_number' => $setNumber,
                'reps_done' => max(5, $target - ($setNumber - 1)),
                'weight' => $base === null ? null : $base + ($step * $week),
                'is_completed' => true,
                'note' => $setNumber === 1 && $week === 2 && $base !== null
                    ? 'الوزن كان مريحًا، أزيد الأسبوع القادم.'
                    : null,
                'client_uuid' => (string) Str::uuid(),
                'synced_at' => $performedOn->copy()->addHours(19),
            ]);
        }
    }

    /**
     * Coaches write reps as free text. Pull the first number out of it and fall
     * back to ten for timed or to-failure work.
     */
    private function repTarget(string $reps): int
    {
        return preg_match('/\d+/', $reps, $matches) === 1 ? (int) $matches[0] : 10;
    }

    private function seedBodyMetrics(User $user): void
    {
        foreach (self::METRICS as $metric) {
            $measuredOn = now()->startOfDay()->subWeeks($metric['weeks_ago'])->toDateString();

            $existing = BodyMetric::query()
                ->where('user_id', $user->getKey())
                ->whereDate('measured_on', $measuredOn)
                ->first();

            ($existing ?? new BodyMetric)->forceFill([
                'user_id' => $user->getKey(),
                'measured_on' => $measuredOn,
                'weight' => $metric['weight'],
                'body_fat' => $metric['body_fat'],
                'notes' => $metric['notes'],
            ])->save();
        }
    }
}
