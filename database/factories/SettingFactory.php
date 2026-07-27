<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    /**
     * @var list<array{name_ar: string, name_en: string, tagline_ar: string, tagline_en: string}>
     */
    private const CLUBS = [
        [
            'name_ar' => 'قسورة الأزرق',
            'name_en' => 'Al-Qaswarah Al-Azraq',
            'tagline_ar' => 'قوّتك تبدأ من هنا',
            'tagline_en' => 'Your strength starts here',
        ],
        [
            'name_ar' => 'نادي الصقر للياقة',
            'name_en' => 'Al-Saqr Fitness Club',
            'tagline_ar' => 'تدريب بانضباط ونتائج تدوم',
            'tagline_en' => 'Disciplined training, lasting results',
        ],
        [
            'name_ar' => 'نادي الحديد',
            'name_en' => 'Iron Club',
            'tagline_ar' => 'حديد وعرق وصبر',
            'tagline_en' => 'Iron, sweat and patience',
        ],
        [
            'name_ar' => 'مركز الأصيل الرياضي',
            'name_en' => 'Al-Aseel Sports Center',
            'tagline_ar' => 'برامج مبنية على أساس',
            'tagline_en' => 'Programs built on fundamentals',
        ],
    ];

    /**
     * @var list<array{ar: string, en: string}>
     */
    private const CITIES = [
        ['ar' => 'الرياض', 'en' => 'Riyadh'],
        ['ar' => 'جدة', 'en' => 'Jeddah'],
        ['ar' => 'الدمام', 'en' => 'Dammam'],
        ['ar' => 'بريدة', 'en' => 'Buraydah'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $club = fake()->randomElement(self::CLUBS);
        $city = fake()->randomElement(self::CITIES);

        return [
            'club_name_ar' => $club['name_ar'],
            'club_name_en' => $club['name_en'],
            'tagline_ar' => $club['tagline_ar'],
            'tagline_en' => $club['tagline_en'],
            'description_ar' => 'نادٍ متكامل للتدريب بالأثقال مع متابعة أسبوعية للمتدربين وبرامج مخصصة حسب المستوى.',
            'description_en' => 'A full strength-training facility with weekly follow-up and programs tailored to each level.',
            'address_ar' => 'طريق الملك عبدالعزيز، حي النخيل',
            'address_en' => 'King Abdulaziz Road, Al-Nakheel District',
            'city_ar' => $city['ar'],
            'city_en' => $city['en'],
            'map_url' => 'https://maps.google.com/?q='.fake()->latitude().','.fake()->longitude(),
            'phone' => fake()->numerify('+9665########'),
            'whatsapp' => fake()->numerify('+9665########'),
            'instagram' => fake()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'logo_path' => null,
        ];
    }

    /**
     * The club identity as it ships before an admin fills it in: name only, no
     * invented contact details.
     */
    public function provisional(): static
    {
        return $this->state(fn (array $attributes): array => [
            'club_name_ar' => 'قسورة الأزرق',
            'club_name_en' => 'Al-Qaswarah Al-Azraq',
            'tagline_ar' => null,
            'tagline_en' => null,
            'description_ar' => null,
            'description_en' => null,
            'address_ar' => null,
            'address_en' => null,
            'city_ar' => null,
            'city_en' => null,
            'map_url' => null,
            'phone' => null,
            'whatsapp' => null,
            'instagram' => null,
            'email' => null,
            'logo_path' => null,
        ]);
    }

    /**
     * A club that has uploaded its logo.
     */
    public function withLogo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'logo_path' => 'settings/logo.png',
        ]);
    }
}
