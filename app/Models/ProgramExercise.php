<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Concerns\HasTranslatableAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramExercise extends Model
{
    use HasFactory;
    use HasTranslatableAttributes;

    /** @var list<string> */
    protected $fillable = [
        'program_day_id',
        'exercise_id',
        'sort',
        'sets',
        'reps',
        'rest_seconds',
        'tempo',
        'weight_note',
        'coach_notes_ar',
        'coach_notes_en',
        'superset_group',
    ];

    /** @var list<string> */
    protected array $translatable = ['coach_notes'];

    public function programDay(): BelongsTo
    {
        return $this->belongsTo(ProgramDay::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function workoutLogs(): HasMany
    {
        return $this->hasMany(WorkoutLog::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'sets' => 'integer',
            'rest_seconds' => 'integer',
        ];
    }
}
