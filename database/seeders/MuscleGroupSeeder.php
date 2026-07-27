<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MuscleGroup;
use Illuminate\Database\Seeder;

/**
 * The standard training split. `icon` holds a Lucide icon name, resolved by the
 * UI layer; `slug` is the stable key everything else references.
 */
class MuscleGroupSeeder extends Seeder
{
    /**
     * @var list<array{name_ar: string, name_en: string, slug: string, icon: string, sort: int}>
     */
    private const GROUPS = [
        ['name_ar' => 'صدر', 'name_en' => 'Chest', 'slug' => 'chest', 'icon' => 'shield', 'sort' => 1],
        ['name_ar' => 'ظهر', 'name_en' => 'Back', 'slug' => 'back', 'icon' => 'bone', 'sort' => 2],
        ['name_ar' => 'أكتاف', 'name_en' => 'Shoulders', 'slug' => 'shoulders', 'icon' => 'move-horizontal', 'sort' => 3],
        ['name_ar' => 'عضلة ذات الرأسين', 'name_en' => 'Biceps', 'slug' => 'biceps', 'icon' => 'biceps-flexed', 'sort' => 4],
        ['name_ar' => 'عضلة ثلاثية الرؤوس', 'name_en' => 'Triceps', 'slug' => 'triceps', 'icon' => 'dumbbell', 'sort' => 5],
        ['name_ar' => 'أرجل', 'name_en' => 'Legs', 'slug' => 'legs', 'icon' => 'footprints', 'sort' => 6],
        ['name_ar' => 'بطن', 'name_en' => 'Core', 'slug' => 'core', 'icon' => 'shield-half', 'sort' => 7],
        ['name_ar' => 'سواعد', 'name_en' => 'Forearms', 'slug' => 'forearms', 'icon' => 'grip', 'sort' => 8],
        ['name_ar' => 'تحمّل', 'name_en' => 'Cardio', 'slug' => 'cardio', 'icon' => 'heart-pulse', 'sort' => 9],
    ];

    public function run(): void
    {
        foreach (self::GROUPS as $group) {
            MuscleGroup::query()->updateOrCreate(
                ['slug' => $group['slug']],
                [
                    'name_ar' => $group['name_ar'],
                    'name_en' => $group['name_en'],
                    'icon' => $group['icon'],
                    'sort' => $group['sort'],
                ]
            );
        }
    }
}
