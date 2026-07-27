<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ManagesTraining;

/**
 * Club membership belongs to the club owner. A coach writes programs; they do
 * not open, suspend or re-point accounts.
 */
class UserPolicy
{
    use ManagesTraining;

    public function viewAny(User $user): bool
    {
        return $this->ownsTheClub($user);
    }

    public function view(User $user, User $model): bool
    {
        return $this->ownsTheClub($user);
    }

    public function create(User $user): bool
    {
        return $this->ownsTheClub($user);
    }

    public function update(User $user, User $model): bool
    {
        return $this->ownsTheClub($user);
    }

    /**
     * Accounts are suspended, never deleted — their logged sets are the
     * trainee's own history. An admin also may not suspend themselves, which
     * would lock the last admin out of the panel.
     */
    public function deactivate(User $user, User $model): bool
    {
        return $this->ownsTheClub($user) && ! $user->is($model);
    }

    public function assignProgram(User $user, User $model): bool
    {
        return $this->ownsTheClub($user);
    }
}
