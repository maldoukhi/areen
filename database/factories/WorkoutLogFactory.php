<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProgramExercise;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WorkoutLog>
 */
class WorkoutLogFactory extends Factory
{
    /**
     * @var list<string>
     */
    private const NOTES = [
        'الوزن كان مريحًا، أزيد الأسبوع القادم.',
        'آخر عدتين كانتا صعبتين.',
        'شعرت بشد بسيط في أسفل الظهر، خففت الوزن.',
        'أنهيت الجولات كاملة بشكل نظيف.',
        'الراحة كانت قصيرة، أطولها المرة القادمة.',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->trainee(),
            'program_exercise_id' => ProgramExercise::factory(),
            'performed_on' => fake()->dateTimeBetween('-8 weeks', 'now')->format('Y-m-d'),
            'set_number' => fake()->numberBetween(1, 5),
            'reps_done' => fake()->numberBetween(6, 15),
            'weight' => fake()->randomElement([20, 25, 30, 35, 40, 50, 60, 70, 80]) + fake()->randomElement([0, 0.5, 2.5]),
            'is_completed' => true,
            'note' => fake()->boolean(15) ? fake()->randomElement(self::NOTES) : null,
            // The browser mints this before the set is ever sent; the server
            // upserts on it so an offline retry can never duplicate a round.
            'client_uuid' => (string) Str::uuid(),
            'synced_at' => now(),
        ];
    }

    public function forUser(User|int $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user instanceof User ? $user->getKey() : $user,
        ]);
    }

    public function forProgramExercise(ProgramExercise|int $programExercise): static
    {
        return $this->state(fn (array $attributes): array => [
            'program_exercise_id' => $programExercise instanceof ProgramExercise
                ? $programExercise->getKey()
                : $programExercise,
        ]);
    }

    /**
     * A round the trainee started and abandoned.
     */
    public function skipped(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_completed' => false,
            'reps_done' => null,
            'weight' => null,
        ]);
    }

    /**
     * Logged offline and still waiting on Background Sync.
     */
    public function pendingSync(): static
    {
        return $this->state(fn (array $attributes): array => [
            'synced_at' => null,
        ]);
    }

    /**
     * A bodyweight round: reps only, no load.
     */
    public function bodyweight(): static
    {
        return $this->state(fn (array $attributes): array => [
            'weight' => null,
        ]);
    }
}
