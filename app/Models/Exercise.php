<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Difficulty;
use App\Support\Concerns\HasTranslatableAttributes;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exercise extends Model
{
    use HasFactory;
    use HasTranslatableAttributes;
    use SoftDeletes;

    /**
     * Matches the id in every YouTube URL shape a coach might paste: the desktop
     * watch link (with the id in any query position), the share link, an embed
     * and a short.
     */
    private const string YOUTUBE_ID_PATTERN = '#(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/))([\w-]{11})#i';

    /** @var list<string> */
    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'muscle_group_id',
        'secondary_muscles',
        'equipment',
        'difficulty',
        'youtube_url',
        'media_path',
        'description_ar',
        'description_en',
        'is_active',
    ];

    /** @var list<string> */
    protected array $translatable = ['name', 'description'];

    public function muscleGroup(): BelongsTo
    {
        return $this->belongsTo(MuscleGroup::class);
    }

    public function programExercises(): HasMany
    {
        return $this->hasMany(ProgramExercise::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    protected function forMuscle(Builder $query, int|MuscleGroup $muscleGroupId): void
    {
        $query->where('muscle_group_id', $muscleGroupId instanceof MuscleGroup ? $muscleGroupId->getKey() : $muscleGroupId);
    }

    protected function youtubeId(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (blank($this->youtube_url)) {
                return null;
            }

            return preg_match(self::YOUTUBE_ID_PATTERN, (string) $this->youtube_url, $matches) === 1
                ? $matches[1]
                : null;
        });
    }

    protected function youtubeThumbnailUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => filled($this->youtube_id)
            ? 'https://i.ytimg.com/vi/'.$this->youtube_id.'/hqdefault.jpg'
            : null);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'secondary_muscles' => 'array',
            'difficulty' => Difficulty::class,
            'is_active' => 'boolean',
        ];
    }
}
