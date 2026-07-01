<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Position;
use App\Models\SchoolClass;
use App\Models\UpJurusan;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestingUserSeeder extends Seeder
{
    /**
     * Seed test accounts for local development.
     */
    public function run(): void
    {
        if (! Position::query()->where('code', Position::TEACHER)->exists()) {
            $this->call(SchoolReferenceSeeder::class);
        }

        $teacherPosition = Position::query()
            ->where('code', Position::TEACHER)
            ->firstOrFail();
        $studentPosition = Position::query()
            ->where('code', Position::STUDENT)
            ->firstOrFail();
        $studentClassId = SchoolClass::query()->value('id');

        foreach ($this->users() as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'password' => 'password',
                    'position_id' => $user['role'] === UserRole::Buyer ? $studentPosition->id : $teacherPosition->id,
                    'class_id' => $user['role'] === UserRole::Buyer ? $studentClassId : null,
                    'up_jurusan_id' => null,
                ],
            );
        }

        $adminJurusan = User::query()
            ->where('email', 'admin.jurusan@educart.test')
            ->firstOrFail();
        $picket = User::query()
            ->where('email', 'picket@educart.test')
            ->firstOrFail();
        $upJurusan = UpJurusan::query()->updateOrCreate(
            ['name' => 'UP RPL'],
            [
                'admin_jurusan_id' => $adminJurusan->id,
                'description' => 'Unit produksi jurusan RPL untuk demo EduCart.',
            ],
        );

        $picket->update(['up_jurusan_id' => $upJurusan->id]);
    }

    /**
     * @return array<int, array{name: string, email: string, role: UserRole}>
     */
    private function users(): array
    {
        return [
            [
                'name' => 'Admin EduCart',
                'email' => 'admin@educart.test',
                'role' => UserRole::Admin,
            ],
            [
                'name' => 'Seller EduCart',
                'email' => 'seller@educart.test',
                'role' => UserRole::Seller,
            ],
            [
                'name' => 'Buyer EduCart',
                'email' => 'buyer@educart.test',
                'role' => UserRole::Buyer,
            ],
            [
                'name' => 'Admin Jurusan EduCart',
                'email' => 'admin.jurusan@educart.test',
                'role' => UserRole::AdminJurusan,
            ],
            [
                'name' => 'Picket Officer EduCart',
                'email' => 'picket@educart.test',
                'role' => UserRole::PicketOfficer,
            ],
        ];
    }
}
