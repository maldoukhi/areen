<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProgramExercise;
use App\Models\User;
use App\Policies\Concerns\ManagesTraining;

class ProgramExercisePolicy
{
    use ManagesTraining;

    public function viewAny(User $user): bool
    {
        return $this->managesTraining($user);
    }

    public function view(User $user, ProgramExercise $row): bool
    {
        return $this->managesTraining($user);
    }

    public function create(User $user): bool
    {
        return $this->managesTraining($user);
    }

    public function update(User $user, ProgramExercise $row): bool
    {
        return $this->managesTraining($user);
    }

    public function delete(User $user, ProgramExercise $row): bool
    {
        return $this->managesTraining($user);
    }
}
