<?php

namespace App\Support;

use App\Enums\UpJurusanStatus;
use App\Enums\UserRole;
use App\Models\UpJurusan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrganizationLifecycleService
{
    public static function closeUpJurusan(UpJurusan $upJurusan, ?User $actor = null): void
    {
        DB::transaction(function () use ($upJurusan, $actor) {
            /** @var UpJurusan $current */
            $current = UpJurusan::query()->lockForUpdate()->findOrFail($upJurusan->id);

            if ($current->status === UpJurusanStatus::Closed) {
                throw ValidationException::withMessages([
                    'up_jurusan' => 'UP Jurusan ini sudah ditutup.',
                ]);
            }

            ActorLifecycle::assertCanDeleteUpJurusan($current);

            $current->update(['status' => UpJurusanStatus::Closed]);

            DomainEventService::record(
                DomainEventService::AGGREGATE_UP_JURUSAN,
                $current->id,
                'up_closed',
                $actor,
                [
                    'from_status' => UpJurusanStatus::Active->value,
                    'to_status' => UpJurusanStatus::Closed->value,
                ],
            );
        });
    }

    public static function deleteUpJurusan(UpJurusan $upJurusan, ?User $actor = null): void
    {
        DB::transaction(function () use ($upJurusan, $actor) {
            /** @var UpJurusan $current */
            $current = UpJurusan::query()->lockForUpdate()->findOrFail($upJurusan->id);

            ActorLifecycle::assertCanDeleteUpJurusan($current);

            if ($current->status !== UpJurusanStatus::Closed) {
                $current->update(['status' => UpJurusanStatus::Closed]);

                DomainEventService::record(
                    DomainEventService::AGGREGATE_UP_JURUSAN,
                    $current->id,
                    'up_closed',
                    $actor,
                    [
                        'from_status' => UpJurusanStatus::Active->value,
                        'to_status' => UpJurusanStatus::Closed->value,
                    ],
                );
            }

            User::query()
                ->where('role', UserRole::PicketOfficer)
                ->where('up_jurusan_id', $current->id)
                ->update(['up_jurusan_id' => null]);

            $current->delete();
        });
    }

    public static function assignAdmin(UpJurusan $upJurusan, User $admin, ?User $actor = null): void
    {
        DB::transaction(function () use ($upJurusan, $admin, $actor) {
            /** @var UpJurusan $current */
            $current = UpJurusan::query()->lockForUpdate()->findOrFail($upJurusan->id);

            self::assertAdminCandidate($admin);
            self::assertCanReassignAdmin($current);

            if (UpJurusan::query()
                ->where('admin_jurusan_id', $admin->id)
                ->whereKeyNot($current->id)
                ->exists()
            ) {
                throw ValidationException::withMessages([
                    'admin_jurusan_id' => 'Admin jurusan hanya dapat mengelola satu UP Jurusan.',
                ]);
            }

            $fromAdminId = $current->admin_jurusan_id;

            if ($fromAdminId === $admin->id) {
                return;
            }

            $current->update(['admin_jurusan_id' => $admin->id]);

            DomainEventService::record(
                DomainEventService::AGGREGATE_UP_JURUSAN,
                $current->id,
                'admin_reassigned',
                $actor,
                [
                    'from_admin_id' => $fromAdminId,
                    'to_admin_id' => $admin->id,
                ],
            );
        });
    }

    public static function reassignAdmin(UpJurusan $upJurusan, User $admin, ?User $actor = null): void
    {
        self::assignAdmin($upJurusan, $admin, $actor);
    }

    public static function assignPicket(UpJurusan $upJurusan, User $picket, ?User $actor = null): void
    {
        DB::transaction(function () use ($upJurusan, $picket, $actor) {
            /** @var UpJurusan $current */
            $current = UpJurusan::query()->lockForUpdate()->findOrFail($upJurusan->id);

            self::assertActive($current);
            self::assertPicketCandidate($picket);

            if ($picket->up_jurusan_id !== null && $picket->up_jurusan_id !== $current->id) {
                throw ValidationException::withMessages([
                    'picket_id' => 'Picket officer sudah ditugaskan ke UP Jurusan lain.',
                ]);
            }

            $currentPicket = User::query()
                ->where('role', UserRole::PicketOfficer)
                ->where('up_jurusan_id', $current->id)
                ->whereKeyNot($picket->id)
                ->lockForUpdate()
                ->first();

            if ($currentPicket !== null) {
                ActorLifecycle::assertCanReassignPicket($current);
                $currentPicket->update(['up_jurusan_id' => null]);

                DomainEventService::record(
                    DomainEventService::AGGREGATE_UP_JURUSAN,
                    $current->id,
                    'picket_reassigned',
                    $actor,
                    [
                        'from_picket_id' => $currentPicket->id,
                        'to_picket_id' => $picket->id,
                    ],
                );
            } else {
                DomainEventService::record(
                    DomainEventService::AGGREGATE_UP_JURUSAN,
                    $current->id,
                    'picket_assigned',
                    $actor,
                    [
                        'picket_id' => $picket->id,
                    ],
                );
            }

            $picket->update(['up_jurusan_id' => $current->id]);
        });
    }

    public static function reassignPicket(UpJurusan $upJurusan, User $picket, ?User $actor = null): void
    {
        self::assignPicket($upJurusan, $picket, $actor);
    }

    public static function unassignPicket(UpJurusan $upJurusan, ?User $actor = null): void
    {
        DB::transaction(function () use ($upJurusan, $actor) {
            /** @var UpJurusan $current */
            $current = UpJurusan::query()->lockForUpdate()->findOrFail($upJurusan->id);

            ActorLifecycle::assertCanReassignPicket($current);

            $picket = User::query()
                ->where('role', UserRole::PicketOfficer)
                ->where('up_jurusan_id', $current->id)
                ->lockForUpdate()
                ->first();

            if ($picket === null) {
                throw ValidationException::withMessages([
                    'picket_id' => 'UP Jurusan ini belum memiliki picket officer.',
                ]);
            }

            $picket->update(['up_jurusan_id' => null]);

            DomainEventService::record(
                DomainEventService::AGGREGATE_UP_JURUSAN,
                $current->id,
                'picket_unassigned',
                $actor,
                [
                    'picket_id' => $picket->id,
                ],
            );
        });
    }

    public static function assertActive(UpJurusan $upJurusan): void
    {
        if ($upJurusan->status === UpJurusanStatus::Closed) {
            throw ValidationException::withMessages([
                'up_jurusan' => 'UP Jurusan yang sudah ditutup tidak dapat diubah penugasannya.',
            ]);
        }
    }

    public static function assertCanReassignAdmin(UpJurusan $upJurusan): void
    {
        if (ActorLifecycle::upJurusanHasActiveTransactions($upJurusan)) {
            throw ValidationException::withMessages([
                'admin_jurusan_id' => 'Admin jurusan tidak dapat diganti selama UP Jurusan masih memiliki proses aktif.',
            ]);
        }
    }

    private static function assertAdminCandidate(User $admin): void
    {
        if ($admin->role !== UserRole::AdminJurusan) {
            throw ValidationException::withMessages([
                'admin_jurusan_id' => 'Hanya user dengan peran admin jurusan yang dapat ditugaskan.',
            ]);
        }
    }

    private static function assertPicketCandidate(User $picket): void
    {
        if ($picket->role !== UserRole::PicketOfficer) {
            throw ValidationException::withMessages([
                'picket_id' => 'Hanya user dengan peran picket officer yang dapat ditugaskan.',
            ]);
        }
    }
}
