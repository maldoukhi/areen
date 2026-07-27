<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Concerns\HasTranslatableAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * The club's own identity — everything Areen must not hardcode so the platform
 * can be handed to a different club. One row only.
 */
class Setting extends Model
{
    use HasFactory;
    use HasTranslatableAttributes;

    public const string CACHE_KEY = 'areen.settings';

    /**
     * Resolved once per request on top of the cache, because settings are read
     * by the layout, the manifest and every public page.
     */
    protected static ?self $current = null;

    /** @var list<string> */
    protected $fillable = [
        'club_name_ar',
        'club_name_en',
        'tagline_ar',
        'tagline_en',
        'description_ar',
        'description_en',
        'address_ar',
        'address_en',
        'city_ar',
        'city_en',
        'map_url',
        'phone',
        'whatsapp',
        'instagram',
        'email',
        'logo_path',
    ];

    /** @var list<string> */
    protected $appends = ['logo_url'];

    /** @var list<string> */
    protected array $translatable = [
        'club_name',
        'tagline',
        'description',
        'address',
        'city',
    ];

    /**
     * Never null, so a fresh install renders instead of failing; the empty
     * instance is cached too and dropped the moment the row is written.
     */
    public static function current(): self
    {
        return static::$current ??= Cache::rememberForever(
            self::CACHE_KEY,
            fn (): self => static::query()->first() ?? new self,
        );
    }

    public static function forget(): void
    {
        static::$current = null;

        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(static fn () => static::forget());
        static::deleted(static fn () => static::forget());
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => filled($this->logo_path)
            ? Storage::disk('public')->url($this->logo_path)
            : null);
    }
}
