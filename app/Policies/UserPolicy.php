<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\ActorLifecycle;
use Illuminate\Validation\ValidationException;

class UserPolicy
{
    public function promoteToSeller(User $actor, User $target): bool
    {
        if ($actor->role !== UserRole::Admin) {
            return false;
        }

        try {
            ActorLifecycle::assertCanPromoteToSeller($target);
        } catch (ValidationException) {
            return false;
        }

        return true;
    }

    public function delete(User $actor, User $target): bool
    {
        if ($actor->id !== $target->id) {
            return false;
        }

        try {
            ActorLifecycle::assertCanDeleteAccount($target);
        } catch (ValidationException) {
            return false;
        }

        return true;
    }
}
