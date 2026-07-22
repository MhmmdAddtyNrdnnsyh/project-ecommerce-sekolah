<?php

namespace Database\Factories;

use App\Enums\UpJurusanStatus;
use App\Enums\UserRole;
use App\Models\UpJurusan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UpJurusan>
 */
class UpJurusanFactory extends Factory
{
    protected $model = UpJurusan::class;

    public function definition(): array
    {
        return [
            'admin_jurusan_id' => User::factory()->create(['role' => UserRole::AdminJurusan])->id,
            'name' => 'UP '.$this->faker->unique()->word(),
            'description' => $this->faker->sentence(),
            'status' => UpJurusanStatus::Active,
        ];
    }
}
