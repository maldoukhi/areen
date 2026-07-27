<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProgramDay;
use App\Models\User;
use App\Policies\Concerns\ManagesTraining;

class ProgramDayPolicy
{
    use ManagesTraining;

    public function viewAny(User $user): bool
    {
        return $this->managesTraining($user);
    }

    public function view(User $user, ProgramDay $day): bool
    {
        return $this->managesTraining($user);
    }

    public function create(User $user): bool
    {
        return $this->managesTraining($user);
    }

    public function update(User $user, ProgramDay $day): bool
    {
        return $this->managesTraining($user);
    }

    public function delete(User $user, ProgramDay $day): bool
    {
        return $this->managesTraining($user);
    }
}
