<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BodyMetric;
use App\Models\User;

/**
 * Weight and body fat are the most personal numbers the platform holds, so the
 * rule is the narrowest one available: only the person they belong to.
 */
class BodyMetricPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, BodyMetric $metric): bool
    {
        return $this->owns($user, $metric);
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, BodyMetric $metric): bool
    {
        return $this->owns($user, $metric);
    }

    public function delete(User $user, BodyMetric $metric): bool
    {
        return $this->owns($user, $metric);
    }

    private function owns(User $user, BodyMetric $metric): bool
    {
        return $user->is_active && $metric->user_id === $user->getKey();
    }
}
