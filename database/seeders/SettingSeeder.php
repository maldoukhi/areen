<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * The club identity row.
 *
 * PROVISIONAL. Only the club name is actually known at this point. Everything
 * else — phone, WhatsApp, Instagram, address, city, map link, logo — is left
 * NULL on purpose and must be filled in from the admin panel. Do not invent
 * contact details here: Areen may be sold to another club, and a fake phone
 * number shipped in a seeder is a phone number someone eventually calls.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::query()->updateOrCreate(
            ['id' => 1],
            [
                'club_name_ar' => 'قسورة الأزرق',
                'club_name_en' => 'Al-Qaswarah Al-Azraq',

                // Placeholders below — replace from the admin panel, not here.
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
                // The club logo is uploaded by the admin; until then the UI falls
                // back to the Areen mark.
                'logo_path' => null,
            ]
        );
    }
}
