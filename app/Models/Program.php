<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProgramLevel;
use App\Support\Concerns\HasTranslatableAttributes;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use HasFactory;
    use HasTranslatableAttributes;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'description_ar',
        'description_en',
        'days_count',
        'level',
        'goal',
        'cover_path',
        'is_public',
        'is_featured',
        'access_code',
        'published_at',
        'sort',
    ];

    /** @var list<string> */
    protected array $translatable = ['name', 'description'];

    public function days(): HasMany
    {
        return $this->hasMany(ProgramDay::class)->orderBy('day_number');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('started_at', 'is_active')
            ->withTimestamps();
    }

    public function hasAccessCode(): bool
    {
        return filled($this->access_code);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * A program is only visible publicly once it is both marked public and its
     * publication date has arrived — a future `published_at` schedules it.
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_public', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    #[Scope]
    protected function featured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort')->orderBy('name_ar');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'days_count' => 'integer',
            'level' => ProgramLevel::class,
            'is_public' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'sort' => 'integer',
        ];
    }
}
