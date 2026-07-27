<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MuscleGroup;
use App\Models\Program;
use App\Models\ProgramDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramDay>
 */
class ProgramDayFactory extends Factory
{
    /**
     * @var list<array{ar: string, en: string}>
     */
    private const TITLES = [
        ['ar' => 'يوم الدفع', 'en' => 'Push Day'],
        ['ar' => 'يوم السحب', 'en' => 'Pull Day'],
        ['ar' => 'يوم الأرجل', 'en' => 'Leg Day'],
        ['ar' => 'الجسم كامل — أ', 'en' => 'Full Body A'],
        ['ar' => 'الجسم كامل — ب', 'en' => 'Full Body B'],
        ['ar' => 'علوي', 'en' => 'Upper Body'],
        ['ar' => 'سفلي', 'en' => 'Lower Body'],
        ['ar' => 'تحمّل وبطن', 'en' => 'Conditioning & Core'],
    ];

    /**
     * `day_number` is unique per program. The factory cannot know the program id
     * at definition time, so it hands out a monotonic number instead; a single
     * program would need more than 250 days in one run to see a collision.
     */
    private static int $dayNumber = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $index = self::$dayNumber++;
        $title = self::TITLES[$index % count(self::TITLES)];

        return [
            'program_id' => Program::factory(),
            'day_number' => ($index % 250) + 1,
            'title_ar' => $title['ar'],
            'title_en' => $title['en'],
            'focus_muscle_id' => MuscleGroup::factory(),
            'is_rest_day' => false,
            'notes_ar' => null,
            'notes_en' => null,
        ];
    }

    /**
     * Attach the day to an existing program.
     */
    public function forProgram(Program|int $program): static
    {
        return $this->state(fn (array $attributes): array => [
            'program_id' => $program instanceof Program ? $program->getKey() : $program,
        ]);
    }

    public function dayNumber(int $number): static
    {
        return $this->state(fn (array $attributes): array => [
            'day_number' => $number,
        ]);
    }

    /**
     * A rest day carries no exercises and no focus muscle.
     */
    public function rest(): static
    {
        return $this->state(fn (array $attributes): array => [
            'title_ar' => 'راحة',
            'title_en' => 'Rest',
            'focus_muscle_id' => null,
            'is_rest_day' => true,
            'notes_ar' => 'مشي خفيف عشرين دقيقة وإطالة للجزء السفلي.',
            'notes_en' => 'Twenty minutes of easy walking and lower-body stretching.',
        ]);
    }

    public function focusOn(MuscleGroup|int|null $muscleGroup): static
    {
        return $this->state(fn (array $attributes): array => [
            'focus_muscle_id' => $muscleGroup instanceof MuscleGroup ? $muscleGroup->getKey() : $muscleGroup,
        ]);
    }
}
