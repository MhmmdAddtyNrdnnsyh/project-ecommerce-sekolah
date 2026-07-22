<?php

namespace Database\Factories;

use App\Enums\UpJurusanConsignmentStatus;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UpJurusanConsignment>
 */
class UpJurusanConsignmentFactory extends Factory
{
    protected $model = UpJurusanConsignment::class;

    public function definition(): array
    {
        $seller = User::factory()->create(['role' => UserRole::Seller]);

        return [
            'seller_id' => $seller->id,
            'product_id' => Product::factory()->create(['seller_id' => $seller->id])->id,
            'up_jurusan_id' => UpJurusan::factory(),
            'requested_quantity' => 10,
            'received_quantity' => 0,
            'sold_quantity' => 0,
            'commission_rate' => null,
            'status' => UpJurusanConsignmentStatus::PendingApproval,
            'note' => null,
        ];
    }
}
