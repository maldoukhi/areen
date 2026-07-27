<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * The training library — programs, days, exercises, muscle groups — is shared
 * between admins and coaches. Club membership (users) and club identity
 * (settings) are not: those stay with the admin.
 *
 * A suspended account keeps its role but loses every permission, so switching
 * `is_active` off is enough to lock somebody out of the panel.
 */
trait ManagesTraining
{
    protected function managesTraining(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isCoach());
    }

    protected function ownsTheClub(User $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
