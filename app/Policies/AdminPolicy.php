<?php

namespace App\Policies;

use App\Models\User;

/**
 * The admin area has a single trusted user, and the 2FA middleware has already vetted the session.
 * Module policies extend this so every action still goes through Gate and can be tightened per module later.
 */
class AdminPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasTwoFactorEnabled();
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function restore(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function forceDelete(User $user): bool
    {
        return $this->viewAny($user);
    }
}
