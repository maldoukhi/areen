<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;
use App\Policies\Concerns\ManagesTraining;

/**
 * The club's own identity — its name, its logo, how it is reached. That is the
 * owner's, not the coach's.
 */
class SettingPolicy
{
    use ManagesTraining;

    public function viewAny(User $user): bool
    {
        return $this->ownsTheClub($user);
    }

    public function view(User $user, Setting $setting): bool
    {
        return $this->ownsTheClub($user);
    }

    public function update(User $user, Setting $setting): bool
    {
        return $this->ownsTheClub($user);
    }
}
