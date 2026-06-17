<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductCatalogSeeder extends Seeder
{
    /**
     * Seed product categories and starter products.
     */
    public function run(): void
    {
        $seller = User::query()->firstOrCreate(
            ['email' => 'seller@educart.test'],
            [
                'name' => 'Seller EduCart',
                'role' => UserRole::Seller,
                'email_verified_at' => now(),
                'password' => 'password',
            ],
        );

        foreach ($this->categories() as $category) {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name']],
            );
        }

        foreach ($this->products() as $product) {
            $category = Category::query()
                ->where('slug', $product['category_slug'])
                ->firstOrFail();

            Product::query()->updateOrCreate(
                ['slug' => $product['slug']],
                [
                    'seller_id' => $seller->id,
                    'category_id' => $category->id,
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'price' => $product['price'],
                    'stock' => $product['stock'],
                    'status' => $product['status'],
                    'image' => null,
                ],
            );
        }
    }

    /**
     * @return array<int, array{name: string, slug: string}>
     */
    private function categories(): array
    {
        return [
            ['name' => 'Alat Tulis', 'slug' => 'alat-tulis'],
            ['name' => 'Makanan Ringan', 'slug' => 'makanan-ringan'],
            ['name' => 'Minuman', 'slug' => 'minuman'],
            ['name' => 'Merchandise Sekolah', 'slug' => 'merchandise-sekolah'],
        ];
    }

    /**
     * @return array<int, array{category_slug: string, name: string, slug: string, description: string, price: int, stock: int, status: ProductStatus}>
     */
    private function products(): array
    {
        return [
            [
                'category_slug' => 'alat-tulis',
                'name' => 'Pulpen Gel Hitam',
                'slug' => 'pulpen-gel-hitam',
                'description' => 'Pulpen gel hitam untuk kebutuhan catatan harian siswa.',
                'price' => 5_000,
                'stock' => 40,
                'status' => ProductStatus::Approved,
            ],
            [
                'category_slug' => 'makanan-ringan',
                'name' => 'Keripik Singkong Pedas',
                'slug' => 'keripik-singkong-pedas',
                'description' => 'Camilan keripik singkong pedas dalam kemasan kecil.',
                'price' => 8_000,
                'stock' => 25,
                'status' => ProductStatus::Pending,
            ],
            [
                'category_slug' => 'minuman',
                'name' => 'Es Teh Manis',
                'slug' => 'es-teh-manis',
                'description' => 'Minuman teh manis dingin untuk jam istirahat.',
                'price' => 4_000,
                'stock' => 30,
                'status' => ProductStatus::Draft,
            ],
        ];
    }
}
