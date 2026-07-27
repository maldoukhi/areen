<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProgramLevel;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    /**
     * @var list<array{ar: string, en: string, slug: string, level: string, goal: string, days: int, desc_ar: string, desc_en: string}>
     */
    private const PROGRAMS = [
        [
            'ar' => 'برنامج المبتدئ — ثلاثة أيام',
            'en' => 'Beginner Full-Body — 3 Days',
            'slug' => 'beginner-full-body-3-day',
            'level' => 'beginner',
            'goal' => 'general-fitness',
            'days' => 3,
            'desc_ar' => 'ثلاثة أيام للجسم كامل تبني أساسًا في الحركات المركّبة قبل الانتقال إلى التقسيم.',
            'desc_en' => 'Three full-body days that build a base in the compound lifts before moving to a split.',
        ],
        [
            'ar' => 'برنامج الدفع والسحب والأرجل',
            'en' => 'Push Pull Legs',
            'slug' => 'push-pull-legs',
            'level' => 'intermediate',
            'goal' => 'hypertrophy',
            'days' => 4,
            'desc_ar' => 'تقسيم كلاسيكي يفصل الدفع عن السحب عن الأرجل مع يوم راحة في المنتصف.',
            'desc_en' => 'A classic split separating push, pull and legs with a rest day in the middle.',
        ],
        [
            'ar' => 'برنامج القوة — خمسة أيام',
            'en' => 'Strength Block — 5 Days',
            'slug' => 'strength-block-5-day',
            'level' => 'advanced',
            'goal' => 'strength',
            'days' => 5,
            'desc_ar' => 'تدرّج في الأوزان على الرفعات الأساسية مع عدات منخفضة وراحة أطول.',
            'desc_en' => 'Progressive loading on the main lifts with lower reps and longer rest.',
        ],
        [
            'ar' => 'برنامج التنشيف — أربعة أيام',
            'en' => 'Cutting Block — 4 Days',
            'slug' => 'cutting-block-4-day',
            'level' => 'intermediate',
            'goal' => 'fat-loss',
            'days' => 4,
            'desc_ar' => 'حجم تدريبي متوسط مع راحة قصيرة وعمل تحمّل في نهاية كل حصة.',
            'desc_en' => 'Moderate volume with short rest and conditioning work at the end of each session.',
        ],
        [
            'ar' => 'برنامج الجسم كامل — يومان',
            'en' => 'Full-Body — 2 Days',
            'slug' => 'full-body-2-day',
            'level' => 'beginner',
            'goal' => 'general-fitness',
            'days' => 2,
            'desc_ar' => 'خيار لمن لا يملك سوى يومين في الأسبوع، يغطي كل العضلات الأساسية.',
            'desc_en' => 'An option for two sessions a week that still covers every muscle group.',
        ],
    ];

    /**
     * Walks PROGRAMS so repeated calls never collide on the unique slug.
     */
    private static int $cursor = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $index = self::$cursor++;
        $program = self::PROGRAMS[$index % count(self::PROGRAMS)];

        return [
            'name_ar' => $program['ar'],
            'name_en' => $program['en'],
            // Always suffixed: `slug` is unique and ProgramSeeder owns the clean
            // ones, so factory rows can never collide with seeded ones.
            'slug' => $program['slug'].'-'.$index,
            'description_ar' => $program['desc_ar'],
            'description_en' => $program['desc_en'],
            'days_count' => $program['days'],
            'level' => $program['level'],
            // Stored as a stable slug; the UI translates it.
            'goal' => $program['goal'],
            'cover_path' => null,
            'is_public' => true,
            'is_featured' => false,
            'access_code' => null,
            'published_at' => now()->subDays(fake()->numberBetween(1, 120)),
            'sort' => $index % count(self::PROGRAMS),
        ];
    }

    /**
     * Visible in the public listing.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_public' => true,
            'published_at' => now()->subDay(),
        ]);
    }

    /**
     * Written but not released yet.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_public' => false,
            'published_at' => null,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_featured' => true,
        ]);
    }

    /**
     * A private program reachable only through its secret link.
     */
    public function restricted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_public' => false,
            'is_featured' => false,
            'published_at' => null,
            'access_code' => Str::upper(Str::random(8)),
        ]);
    }

    public function beginner(): static
    {
        return $this->state(fn (array $attributes): array => [
            'level' => ProgramLevel::Beginner->value,
        ]);
    }

    public function intermediate(): static
    {
        return $this->state(fn (array $attributes): array => [
            'level' => ProgramLevel::Intermediate->value,
        ]);
    }

    public function advanced(): static
    {
        return $this->state(fn (array $attributes): array => [
            'level' => ProgramLevel::Advanced->value,
        ]);
    }

    public function daysCount(int $days): static
    {
        return $this->state(fn (array $attributes): array => [
            'days_count' => $days,
        ]);
    }
}
