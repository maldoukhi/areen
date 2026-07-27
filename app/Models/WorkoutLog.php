<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutLog extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'program_exercise_id',
        'performed_on',
        'set_number',
        'reps_done',
        'weight',
        'is_completed',
        'note',
        'client_uuid',
        'synced_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function programExercise(): BelongsTo
    {
        return $this->belongsTo(ProgramExercise::class);
    }

    /**
     * A row written straight from the browser while offline carries no
     * `synced_at` until the background sync has acknowledged it.
     */
    public function isSynced(): bool
    {
        return $this->synced_at !== null;
    }

    #[Scope]
    protected function forUser(Builder $query, int|User $userId): void
    {
        $query->where('user_id', $userId instanceof User ? $userId->getKey() : $userId);
    }

    #[Scope]
    protected function onDate(Builder $query, DateTimeInterface|string $date): void
    {
        $query->whereDate('performed_on', $date);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'performed_on' => 'date',
            'set_number' => 'integer',
            'reps_done' => 'integer',
            'weight' => 'decimal:2',
            'is_completed' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }
}
