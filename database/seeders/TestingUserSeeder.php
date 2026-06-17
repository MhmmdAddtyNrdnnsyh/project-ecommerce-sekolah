<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Position;
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

        foreach ($this->users() as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'email_verified_at' => now(),
                    'password' => 'password',
                    'position_id' => $teacherPosition->id,
                    'class_id' => null,
                ],
            );
        }
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
        ];
    }
}
