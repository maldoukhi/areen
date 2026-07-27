<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Difficulty;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exercise>
 */
class ExerciseFactory extends Factory
{
    /**
     * A small pool of real lifts. The full library lives in ExerciseSeeder; this
     * only needs to produce believable rows for tests.
     *
     * @var list<array{ar: string, en: string, slug: string, equipment: string, difficulty: string, secondary: list<string>, desc_ar: string, desc_en: string}>
     */
    private const EXERCISES = [
        [
            'ar' => 'ضغط بنش بالبار',
            'en' => 'Barbell Bench Press',
            'slug' => 'barbell-bench-press',
            'equipment' => 'barbell',
            'difficulty' => 'intermediate',
            'secondary' => ['triceps', 'shoulders'],
            'desc_ar' => 'استلقِ على البنش وأنزل البار إلى منتصف الصدر ثم ادفعه للأعلى. الخطأ الشائع ارتداد البار من الصدر.',
            'desc_en' => 'Lower the bar to mid-chest under control, then press back to lockout. The common mistake is bouncing the bar off the chest.',
        ],
        [
            'ar' => 'سحب أمامي بالكيبل',
            'en' => 'Lat Pulldown',
            'slug' => 'lat-pulldown',
            'equipment' => 'cable',
            'difficulty' => 'beginner',
            'secondary' => ['biceps', 'forearms'],
            'desc_ar' => 'اسحب البار إلى أعلى الصدر مع خفض لوح الكتف أولًا. الخطأ الشائع سحب البار خلف الرقبة.',
            'desc_en' => 'Pull the bar to the upper chest, leading with the shoulder blades. The common mistake is pulling behind the neck.',
        ],
        [
            'ar' => 'سكوات خلفي بالبار',
            'en' => 'Barbell Back Squat',
            'slug' => 'barbell-back-squat',
            'equipment' => 'barbell',
            'difficulty' => 'intermediate',
            'secondary' => ['core', 'back'],
            'desc_ar' => 'انزل بالورك للخلف والأسفل حتى يوازي الفخذ الأرض. الخطأ الشائع انهيار الركبة للداخل عند الصعود.',
            'desc_en' => 'Sit down and back until the thighs reach parallel. The common mistake is the knees caving inward on the way up.',
        ],
        [
            'ar' => 'رفرفة جانبية بالدمبل',
            'en' => 'Dumbbell Lateral Raise',
            'slug' => 'dumbbell-lateral-raise',
            'equipment' => 'dumbbell',
            'difficulty' => 'beginner',
            'secondary' => [],
            'desc_ar' => 'ارفع الدمبل إلى الجانب حتى مستوى الكتف مع انحناء بسيط في المرفق. الخطأ الشائع المرجحة من الورك.',
            'desc_en' => 'Raise the dumbbells to shoulder height with a slight elbow bend. The common mistake is swinging from the hips.',
        ],
        [
            'ar' => 'تمديد أرجل على الجهاز',
            'en' => 'Leg Extension',
            'slug' => 'leg-extension',
            'equipment' => 'machine',
            'difficulty' => 'beginner',
            'secondary' => [],
            'desc_ar' => 'مدّ الركبة حتى الاستقامة واثبت لحظة ثم انزل بتحكّم. الخطأ الشائع رمي الوزن للأعلى بمرجحة.',
            'desc_en' => 'Extend the knees to straight, hold briefly, then lower under control. The common mistake is throwing the weight up.',
        ],
        [
            'ar' => 'عقلة',
            'en' => 'Pull-Up',
            'slug' => 'pull-up',
            'equipment' => 'bodyweight',
            'difficulty' => 'advanced',
            'secondary' => ['biceps', 'forearms'],
            'desc_ar' => 'اسحب جسمك حتى يتجاوز الذقن مستوى البار. الخطأ الشائع المرجحة بالأرجل لاستكمال العدة.',
            'desc_en' => 'Pull up until the chin clears the bar. The common mistake is kipping with the legs to finish the rep.',
        ],
        [
            'ar' => 'مرجحة بالبار',
            'en' => 'Barbell Curl',
            'slug' => 'barbell-curl',
            'equipment' => 'barbell',
            'difficulty' => 'beginner',
            'secondary' => ['forearms'],
            'desc_ar' => 'ارفع البار بثني المرفق فقط مع بقاء العضد ملاصقًا للجذع. الخطأ الشائع تقديم المرفق للأمام.',
            'desc_en' => 'Curl by bending only at the elbow, upper arm pinned to the torso. The common mistake is letting the elbows drift forward.',
        ],
        [
            'ar' => 'تمرين البلانك',
            'en' => 'Plank',
            'slug' => 'plank',
            'equipment' => 'bodyweight',
            'difficulty' => 'beginner',
            'secondary' => ['shoulders'],
            'desc_ar' => 'ارتكز على الساعدين وأطراف القدم بجسم مستقيم وبطن مشدود. الخطأ الشائع رفع الورك للتخفيف من الجهد.',
            'desc_en' => 'Support yourself on forearms and toes with a straight, braced body. The common mistake is piking the hips up.',
        ],
        [
            'ar' => 'تمديد ترايسبس بالكيبل',
            'en' => 'Cable Triceps Pushdown',
            'slug' => 'cable-triceps-pushdown',
            'equipment' => 'cable',
            'difficulty' => 'beginner',
            'secondary' => [],
            'desc_ar' => 'ادفع المقبض للأسفل حتى امتداد المرفق مع بقاء العضد ثابتًا. الخطأ الشائع الميل بالجسم على المقبض.',
            'desc_en' => 'Push down to full elbow extension with the upper arm locked at your side. The common mistake is leaning onto the bar.',
        ],
        [
            'ar' => 'رفعة ميتة رومانية',
            'en' => 'Romanian Deadlift',
            'slug' => 'romanian-deadlift',
            'equipment' => 'barbell',
            'difficulty' => 'intermediate',
            'secondary' => ['back', 'core'],
            'desc_ar' => 'ادفع الورك للخلف وأنزل البار بمحاذاة الساق حتى تشعر بشد في الخلفية. الخطأ الشائع ثني الركبة كثيرًا.',
            'desc_en' => 'Push the hips back and lower the bar along the legs. The common mistake is bending the knees too much.',
        ],
        [
            'ar' => 'قفز الحبل',
            'en' => 'Jump Rope',
            'slug' => 'jump-rope',
            'equipment' => 'bodyweight',
            'difficulty' => 'intermediate',
            'secondary' => ['legs', 'forearms'],
            'desc_ar' => 'اقفز ارتفاعًا بسيطًا من مشط القدم مع دوران الحبل من الرسغ. الخطأ الشائع القفز عاليًا.',
            'desc_en' => 'Take small hops off the balls of the feet, turning the rope from the wrists. The common mistake is jumping too high.',
        ],
        [
            'ar' => 'ضغط أرجل على الجهاز',
            'en' => 'Leg Press',
            'slug' => 'leg-press',
            'equipment' => 'machine',
            'difficulty' => 'beginner',
            'secondary' => ['core'],
            'desc_ar' => 'ادفع المنصة حتى امتداد الركبة دون قفلها. الخطأ الشائع رفع أسفل الظهر عن المسند في النزول العميق.',
            'desc_en' => 'Press to near lockout without snapping the knees. The common mistake is the lower back lifting off the pad.',
        ],
    ];

    /**
     * Walks EXERCISES so repeated calls never collide on the unique slug.
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
        $exercise = self::EXERCISES[$index % count(self::EXERCISES)];
        $suffix = $index >= count(self::EXERCISES) ? '-'.$index : '';

        return [
            'name_ar' => $exercise['ar'],
            'name_en' => $exercise['en'],
            'slug' => $exercise['slug'].$suffix,
            'muscle_group_id' => MuscleGroup::factory(),
            'secondary_muscles' => $exercise['secondary'],
            // Stored as a stable slug; the UI translates it.
            'equipment' => $exercise['equipment'],
            'difficulty' => $exercise['difficulty'],
            // Real media is uploaded from the admin panel, never faked.
            'youtube_url' => null,
            'media_path' => null,
            'description_ar' => $exercise['desc_ar'],
            'description_en' => $exercise['desc_en'],
            'is_active' => true,
        ];
    }

    /**
     * Attach the exercise to an existing muscle group.
     */
    public function forMuscleGroup(MuscleGroup|int $muscleGroup): static
    {
        return $this->state(fn (array $attributes): array => [
            'muscle_group_id' => $muscleGroup instanceof MuscleGroup ? $muscleGroup->getKey() : $muscleGroup,
        ]);
    }

    public function beginner(): static
    {
        return $this->state(fn (array $attributes): array => [
            'difficulty' => Difficulty::Beginner->value,
        ]);
    }

    public function intermediate(): static
    {
        return $this->state(fn (array $attributes): array => [
            'difficulty' => Difficulty::Intermediate->value,
        ]);
    }

    public function advanced(): static
    {
        return $this->state(fn (array $attributes): array => [
            'difficulty' => Difficulty::Advanced->value,
        ]);
    }

    /**
     * Hidden from the public library but kept for existing programs.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * An exercise that already has a demo video attached.
     */
    public function withMedia(): static
    {
        return $this->state(fn (array $attributes): array => [
            'youtube_url' => 'https://www.youtube.com/watch?v='.fake()->regexify('[A-Za-z0-9_-]{11}'),
            'media_path' => 'exercises/'.$attributes['slug'].'.webp',
        ]);
    }
}
