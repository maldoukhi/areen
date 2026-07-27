<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Program;
use App\Models\User;
use App\Policies\Concerns\ManagesTraining;

class ProgramPolicy
{
    use ManagesTraining;

    public function viewAny(User $user): bool
    {
        return $this->managesTraining($user);
    }

    public function view(User $user, Program $program): bool
    {
        return $this->managesTraining($user);
    }

    public function create(User $user): bool
    {
        return $this->managesTraining($user);
    }

    public function update(User $user, Program $program): bool
    {
        return $this->managesTraining($user);
    }

    public function delete(User $user, Program $program): bool
    {
        return $this->managesTraining($user);
    }

    public function restore(User $user, Program $program): bool
    {
        return $this->managesTraining($user);
    }

    /**
     * Erasing a program erases every logged set that hangs off its days, so it
     * stays with the club owner.
     */
    public function forceDelete(User $user, Program $program): bool
    {
        return $this->ownsTheClub($user);
    }
}
