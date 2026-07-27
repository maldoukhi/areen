<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Order matters: muscle groups feed exercises, exercises feed programs, and
     * programs feed the demo trainee's logged history.
     */
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            MuscleGroupSeeder::class,
            ExerciseSeeder::class,
            ProgramSeeder::class,
        ]);

        // Demo accounts and invented training history never belong on a club's
        // production database.
        if (app()->environment('local', 'testing')) {
            $this->call(DemoUserSeeder::class);
        }
    }
}
