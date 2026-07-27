<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MuscleGroup;
use App\Models\User;
use App\Policies\Concerns\ManagesTraining;

class MuscleGroupPolicy
{
    use ManagesTraining;

    public function viewAny(User $user): bool
    {
        return $this->managesTraining($user);
    }

    public function view(User $user, MuscleGroup $muscleGroup): bool
    {
        return $this->managesTraining($user);
    }

    public function create(User $user): bool
    {
        return $this->managesTraining($user);
    }

    public function update(User $user, MuscleGroup $muscleGroup): bool
    {
        return $this->managesTraining($user);
    }

    /**
     * The exercises table restricts the delete at the database level, so a group
     * that is still in use cannot be removed whatever this returns.
     */
    public function delete(User $user, MuscleGroup $muscleGroup): bool
    {
        return $this->managesTraining($user);
    }
}
