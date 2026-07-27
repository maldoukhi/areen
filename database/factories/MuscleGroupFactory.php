<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MuscleGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MuscleGroup>
 */
class MuscleGroupFactory extends Factory
{
    /**
     * The real training split. `slug` is unique in the schema, so the factory
     * walks this list in order and suffixes once it wraps around.
     *
     * @var list<array{ar: string, en: string, slug: string, icon: string}>
     */
    private const GROUPS = [
        ['ar' => 'صدر', 'en' => 'Chest', 'slug' => 'chest', 'icon' => 'shield'],
        ['ar' => 'ظهر', 'en' => 'Back', 'slug' => 'back', 'icon' => 'bone'],
        ['ar' => 'أكتاف', 'en' => 'Shoulders', 'slug' => 'shoulders', 'icon' => 'move-horizontal'],
        ['ar' => 'عضلة ذات الرأسين', 'en' => 'Biceps', 'slug' => 'biceps', 'icon' => 'biceps-flexed'],
        ['ar' => 'عضلة ثلاثية الرؤوس', 'en' => 'Triceps', 'slug' => 'triceps', 'icon' => 'dumbbell'],
        ['ar' => 'أرجل', 'en' => 'Legs', 'slug' => 'legs', 'icon' => 'footprints'],
        ['ar' => 'بطن', 'en' => 'Core', 'slug' => 'core', 'icon' => 'shield-half'],
        ['ar' => 'سواعد', 'en' => 'Forearms', 'slug' => 'forearms', 'icon' => 'grip'],
        ['ar' => 'تحمّل', 'en' => 'Cardio', 'slug' => 'cardio', 'icon' => 'heart-pulse'],
    ];

    /**
     * Walks GROUPS so repeated calls never collide on the unique slug.
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
        $group = self::GROUPS[$index % count(self::GROUPS)];
        $suffix = $index >= count(self::GROUPS) ? '-'.$index : '';

        return [
            'name_ar' => $group['ar'],
            'name_en' => $group['en'],
            'slug' => $group['slug'].$suffix,
            'icon' => $group['icon'],
            'sort' => ($index % count(self::GROUPS)) + 1,
        ];
    }

    /**
     * Pin the group to one of the real split entries by its slug.
     */
    public function named(string $slug): static
    {
        $group = collect(self::GROUPS)->firstWhere('slug', $slug) ?? self::GROUPS[0];

        return $this->state(fn (array $attributes): array => [
            'name_ar' => $group['ar'],
            'name_en' => $group['en'],
            'slug' => $group['slug'],
            'icon' => $group['icon'],
        ]);
    }
}
