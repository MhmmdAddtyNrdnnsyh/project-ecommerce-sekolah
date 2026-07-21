<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\UpJurusan;
use App\Models\User;
use App\Support\ActorLifecycle;
use Illuminate\Validation\ValidationException;

class UpJurusanPolicy
{
    public function reassignPicket(User $actor, UpJurusan $upJurusan): bool
    {
        if ($actor->role !== UserRole::AdminJurusan || $upJurusan->admin_jurusan_id !== $actor->id) {
            return false;
        }

        try {
            ActorLifecycle::assertCanReassignPicket($upJurusan);
        } catch (ValidationException) {
            return false;
        }

        return true;
    }

    public function delete(User $actor, UpJurusan $upJurusan): bool
    {
        if ($actor->role !== UserRole::AdminJurusan || $upJurusan->admin_jurusan_id !== $actor->id) {
            return false;
        }

        try {
            ActorLifecycle::assertCanDeleteUpJurusan($upJurusan);
        } catch (ValidationException) {
            return false;
        }

        return true;
    }
}
