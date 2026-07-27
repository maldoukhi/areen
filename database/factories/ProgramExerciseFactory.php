<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\ProgramDay;
use App\Models\ProgramExercise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramExercise>
 */
class ProgramExerciseFactory extends Factory
{
    /**
     * Coaches write rep targets as free text, so these are the real strings.
     *
     * @var list<string>
     */
    private const REPS = ['6-8', '8-10', '8-12', '10-12', '12-15', '15-20', 'الفشل', '٣٠ ثانية'];

    /**
     * @var list<string>
     */
    private const TEMPOS = ['2-0-2', '3-1-1', '3-0-1', '2-1-2'];

    /**
     * @var list<string>
     */
    private const NOTES_AR = [
        'انزل ببطء وتحكّم، ولا ترتد من الصدر.',
        'ثبّت لوح الكتف قبل أول عدة وابقَ عليه حتى النهاية.',
        'ابدأ بوزن تُنهي به كل الجولات بشكل نظيف، ثم زد الأسبوع القادم.',
        'خذ نفسًا عميقًا وشد البطن قبل النزول.',
        'لو انكسر الشكل قبل آخر عدة أوقف الجولة، الشكل قبل الوزن.',
        'المدى الكامل أهم من الوزن في هذه الحركة.',
    ];

    /**
     * @var list<string>
     */
    private const NOTES_EN = [
        'Lower slowly and under control; do not bounce off the chest.',
        'Set the shoulder blades before the first rep and hold that position throughout.',
        'Start with a weight you can finish every round with clean form, then add next week.',
        'Take a deep breath and brace the abdomen before you descend.',
        'If form breaks before the last rep, end the round. Form comes before load.',
        'Full range of motion matters more than the weight on this one.',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hasNote = fake()->boolean(35);
        $noteIndex = fake()->numberBetween(0, count(self::NOTES_AR) - 1);

        return [
            'program_day_id' => ProgramDay::factory(),
            'exercise_id' => Exercise::factory(),
            'sort' => fake()->numberBetween(0, 6),
            'sets' => fake()->numberBetween(3, 5),
            'reps' => fake()->randomElement(self::REPS),
            'rest_seconds' => fake()->randomElement([60, 90, 120, 180]),
            'tempo' => fake()->boolean(25) ? fake()->randomElement(self::TEMPOS) : null,
            'weight_note' => null,
            'coach_notes_ar' => $hasNote ? self::NOTES_AR[$noteIndex] : null,
            'coach_notes_en' => $hasNote ? self::NOTES_EN[$noteIndex] : null,
            'superset_group' => null,
        ];
    }

    public function forDay(ProgramDay|int $day): static
    {
        return $this->state(fn (array $attributes): array => [
            'program_day_id' => $day instanceof ProgramDay ? $day->getKey() : $day,
        ]);
    }

    public function forExercise(Exercise|int $exercise): static
    {
        return $this->state(fn (array $attributes): array => [
            'exercise_id' => $exercise instanceof Exercise ? $exercise->getKey() : $exercise,
        ]);
    }

    /**
     * Rows sharing a group are performed back to back.
     */
    public function superset(string $group = 'A'): static
    {
        return $this->state(fn (array $attributes): array => [
            'superset_group' => $group,
            'rest_seconds' => 60,
        ]);
    }

    /**
     * A heavy main lift: fewer rounds, longer rest.
     */
    public function heavy(): static
    {
        return $this->state(fn (array $attributes): array => [
            'sets' => 5,
            'reps' => '5',
            'rest_seconds' => 180,
        ]);
    }
}
