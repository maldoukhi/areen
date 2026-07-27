<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Exercise;
use App\Models\User;
use App\Policies\Concerns\ManagesTraining;

class ExercisePolicy
{
    use ManagesTraining;

    public function viewAny(User $user): bool
    {
        return $this->managesTraining($user);
    }

    public function view(User $user, Exercise $exercise): bool
    {
        return $this->managesTraining($user);
    }

    public function create(User $user): bool
    {
        return $this->managesTraining($user);
    }

    public function update(User $user, Exercise $exercise): bool
    {
        return $this->managesTraining($user);
    }

    public function delete(User $user, Exercise $exercise): bool
    {
        return $this->managesTraining($user);
    }

    public function restore(User $user, Exercise $exercise): bool
    {
        return $this->managesTraining($user);
    }

    public function forceDelete(User $user, Exercise $exercise): bool
    {
        return $this->ownsTheClub($user);
    }
}
