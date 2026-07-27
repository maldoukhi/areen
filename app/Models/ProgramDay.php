<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Concerns\HasTranslatableAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramDay extends Model
{
    use HasFactory;
    use HasTranslatableAttributes;

    /** @var list<string> */
    protected $fillable = [
        'program_id',
        'day_number',
        'title_ar',
        'title_en',
        'focus_muscle_id',
        'is_rest_day',
        'notes_ar',
        'notes_en',
    ];

    /** @var list<string> */
    protected array $translatable = ['title', 'notes'];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function focusMuscle(): BelongsTo
    {
        return $this->belongsTo(MuscleGroup::class, 'focus_muscle_id');
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(ProgramExercise::class)->orderBy('sort');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
            'is_rest_day' => 'boolean',
        ];
    }
}
