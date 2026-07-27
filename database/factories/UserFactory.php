<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Real Arabic names. Faker's ar locale produces transliterated noise, so the
     * pool is curated by hand.
     *
     * @var list<string>
     */
    private const NAMES = [
        'أحمد العنزي',
        'عبدالله الحربي',
        'خالد الشمري',
        'فيصل الدوسري',
        'سلطان القحطاني',
        'ماجد المطيري',
        'بندر الزهراني',
        'تركي العتيبي',
        'ناصر الغامدي',
        'سعود البقمي',
        'عمر الجهني',
        'ياسر السبيعي',
        'مشعل الرشيدي',
        'رائد الخالدي',
        'طلال السهلي',
    ];

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(self::NAMES),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Trainee->value,
            'locale' => 'ar',
            'phone' => fake()->numerify('+9665########'),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * A club administrator with full access to the admin panel.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Admin->value,
        ]);
    }

    /**
     * A coach who writes programs but does not own club settings.
     */
    public function coach(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Coach->value,
        ]);
    }

    /**
     * A trainee who follows a program and logs sets.
     */
    public function trainee(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Trainee->value,
        ]);
    }

    /**
     * A user who browses the platform in English.
     */
    public function english(): static
    {
        return $this->state(fn (array $attributes): array => [
            'locale' => 'en',
        ]);
    }

    /**
     * A suspended account: kept for history but blocked from signing in.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
