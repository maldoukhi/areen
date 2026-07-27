<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BodyMetric;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BodyMetric>
 */
class BodyMetricFactory extends Factory
{
    /**
     * @var list<string>
     */
    private const NOTES = [
        'قياس الصباح قبل الإفطار.',
        'النوم كان قليلًا هذا الأسبوع.',
        'التزمت بالسعرات خمسة أيام من سبعة.',
        'الوزن ثابت لكن المقاس نزل.',
    ];

    /**
     * `(user_id, measured_on)` is unique, so each row steps one day back rather
     * than picking a random date that could repeat for the same trainee.
     */
    private static int $dayOffset = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->trainee(),
            'measured_on' => now()->subDays(self::$dayOffset++)->toDateString(),
            'weight' => fake()->randomFloat(1, 62, 105),
            'body_fat' => fake()->randomFloat(1, 10, 30),
            'notes' => fake()->boolean(25) ? fake()->randomElement(self::NOTES) : null,
        ];
    }

    public function forUser(User|int $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user instanceof User ? $user->getKey() : $user,
        ]);
    }

    public function measuredOn(string $date): static
    {
        return $this->state(fn (array $attributes): array => [
            'measured_on' => $date,
        ]);
    }

    /**
     * Weight only: most trainees never measure body fat.
     */
    public function weightOnly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'body_fat' => null,
        ]);
    }
}
