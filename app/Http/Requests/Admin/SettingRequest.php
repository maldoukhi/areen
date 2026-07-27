<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The club's identity. There are deliberately no colour fields — the palette is
 * a fixed design token set (DESIGN.md §2), not club data.
 */
class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('update', Setting::current());
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return self::rulesFor();
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function rulesFor(): array
    {
        return [
            'club_name_ar' => ['required', 'string', 'max:255'],
            'club_name_en' => ['nullable', 'string', 'max:255'],
            'tagline_ar' => ['nullable', 'string', 'max:255'],
            'tagline_en' => ['nullable', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'address_ar' => ['nullable', 'string', 'max:255'],
            'address_en' => ['nullable', 'string', 'max:255'],
            'city_ar' => ['nullable', 'string', 'max:255'],
            'city_en' => ['nullable', 'string', 'max:255'],
            'map_url' => ['nullable', 'url', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
            'instagram' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return self::attributeNames();
    }

    /**
     * @return array<string, string>
     */
    public static function attributeNames(): array
    {
        return [
            'club_name_ar' => __('admin.settings.club_name_ar'),
            'club_name_en' => __('admin.settings.club_name_en'),
            'tagline_ar' => __('admin.settings.tagline_ar'),
            'tagline_en' => __('admin.settings.tagline_en'),
            'description_ar' => __('admin.settings.description_ar'),
            'description_en' => __('admin.settings.description_en'),
            'address_ar' => __('admin.settings.address_ar'),
            'address_en' => __('admin.settings.address_en'),
            'city_ar' => __('admin.settings.city_ar'),
            'city_en' => __('admin.settings.city_en'),
            'map_url' => __('admin.settings.map_url'),
            'phone' => __('admin.settings.phone'),
            'whatsapp' => __('admin.settings.whatsapp'),
            'instagram' => __('admin.settings.instagram'),
            'email' => __('admin.settings.email'),
            'logo' => __('admin.settings.logo'),
        ];
    }
}
