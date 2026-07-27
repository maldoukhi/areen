<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProgramExercise;
use App\Models\User;
use App\Models\WorkoutLog;

/**
 * A logged round is the trainee's own history. Nobody else reads it, nobody else
 * writes it — not a coach, not the club owner. The panel already shows a
 * trainee's summary through its own policy; this one guards the trainee surface.
 *
 * Two separate questions live here, and the sync endpoint asks both:
 *
 *  1. `update` — does this row already exist, and is it mine? That is what stops
 *     one account overwriting another's set by guessing its `client_uuid`.
 *  2. `logFor` — am I allowed to write against this `program_exercise_id` at all?
 *     Ownership of the row is not enough: a trainee may not invent history on a
 *     program that was never attached to their account.
 */
class WorkoutLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, WorkoutLog $log): bool
    {
        return $this->owns($user, $log);
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, WorkoutLog $log): bool
    {
        return $this->owns($user, $log);
    }

    public function delete(User $user, WorkoutLog $log): bool
    {
        return $this->owns($user, $log);
    }

    /**
     * May this account record a round against this prescribed exercise?
     *
     * Called with the class rather than an instance — `$user->can('logFor',
     * [WorkoutLog::class, $programExercise])` — because the row being authorised
     * does not exist yet.
     *
     * Deliberately not narrowed to `isTrainee()`: an admin or a coach who has a
     * program attached to their own account is training too, and refusing them
     * here would be a 403 with no explanation behind it.
     */
    public function logFor(User $user, ProgramExercise $programExercise): bool
    {
        if (! $user->is_active) {
            return false;
        }

        $programId = $programExercise->programDay?->program_id;

        if ($programId === null) {
            return false;
        }

        return $user->programs()
            ->whereKey($programId)
            ->exists();
    }

    private function owns(User $user, WorkoutLog $log): bool
    {
        return $user->is_active && $log->user_id === $user->getKey();
    }
}
