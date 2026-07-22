<?php

namespace Database\Seeders;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductFulfillmentType;
use App\Enums\ProductSalesMethod;
use App\Enums\ProductStatus;
use App\Enums\UpJurusanConsignmentStatus;
use App\Enums\UserRole;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\UpJurusanPosSale;
use App\Models\UpJurusanStockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductCatalogSeeder extends Seeder
{
    /**
     * Seed product categories, demo products, and lightweight demo transactions.
     */
    public function run(): void
    {
        $seller = User::query()->firstOrCreate(
            ['email' => 'seller@educart.test'],
            [
                'name' => 'Seller EduCart',
                'role' => UserRole::Seller,
                'password' => 'password',
            ],
        );
        $buyer = User::query()->firstOrCreate(
            ['email' => 'buyer@educart.test'],
            [
                'name' => 'Buyer EduCart',
                'role' => UserRole::Buyer,
                'password' => 'password',
            ],
        );
        $picket = User::query()
            ->where('email', 'picket@educart.test')
            ->firstOrFail();
        $upJurusan = UpJurusan::query()
            ->where('name', 'UP RPL')
            ->firstOrFail();

        $this->seedCategories();
        $products = $this->seedProducts($seller, $upJurusan);
        $consignment = $this->seedConsignment($seller, $upJurusan, $products['risol']);

        $this->seedBuyerCart($buyer, $products['pulpen']);
        $this->seedOnlineOrders($buyer, $seller, $products['pulpen']);
        $this->seedPosSale($picket, $consignment);
    }

    private function seedCategories(): void
    {
        foreach ($this->categories() as $category) {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name']],
            );
        }
    }

    /**
     * @return array<string, Product>
     */
    private function seedProducts(User $seller, UpJurusan $upJurusan): array
    {
        $products = [];

        foreach ($this->products() as $product) {
            $category = Category::query()
                ->where('slug', $product['category_slug'])
                ->firstOrFail();

            $products[$product['key']] = Product::query()->updateOrCreate(
                ['slug' => $product['slug']],
                [
                    'seller_id' => $product['owner'] === 'seller' ? $seller->id : null,
                    'up_jurusan_id' => $product['owner'] === 'up_jurusan' ? $upJurusan->id : null,
                    'category_id' => $category->id,
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'price' => $product['price'],
                    'stock' => $product['stock'],
                    'sales_method' => $product['sales_method'],
                    'fulfillment_type' => $product['fulfillment_type'],
                    'pre_order_estimate_days' => $product['pre_order_estimate_days'],
                    'pre_order_deadline' => $product['pre_order_deadline'],
                    'pre_order_min_quantity' => $product['pre_order_min_quantity'],
                    'pre_order_note' => $product['pre_order_note'],
                    'status' => $product['status'],
                    'image' => null,
                    'rejection_reason' => null,
                ],
            );
        }

        return $products;
    }

    private function seedConsignment(User $seller, UpJurusan $upJurusan, Product $product): UpJurusanConsignment
    {
        $consignment = UpJurusanConsignment::query()->updateOrCreate(
            [
                'seller_id' => $seller->id,
                'product_id' => $product->id,
                'up_jurusan_id' => $upJurusan->id,
            ],
            [
                'requested_quantity' => 20,
                'received_quantity' => 12,
                'sold_quantity' => 2,
                'commission_rate' => 10,
                'status' => UpJurusanConsignmentStatus::Received,
                'note' => 'Demo titipan risol mayo untuk POS UP RPL.',
            ],
        );

        UpJurusanStockMovement::query()->updateOrCreate(
            [
                'up_jurusan_consignment_id' => $consignment->id,
                'up_jurusan_pos_sale_id' => null,
                'type' => 'in',
                'note' => 'Demo barang diterima picket.',
            ],
            [
                'product_id' => null,
                'order_id' => null,
                'user_id' => $upJurusan->admin_jurusan_id,
                'quantity' => 12,
                'unit_price' => 0,
                'gross_amount' => 0,
                'commission_amount' => 0,
                'seller_amount' => 0,
            ],
        );

        return $consignment;
    }

    private function seedBuyerCart(User $buyer, Product $product): void
    {
        CartItem::query()->updateOrCreate(
            [
                'user_id' => $buyer->id,
                'product_id' => $product->id,
            ],
            ['quantity' => 1],
        );
    }

    private function seedOnlineOrders(User $buyer, User $seller, Product $pulpen): void
    {
        $unpaidOrder = Order::query()->updateOrCreate(
            ['code' => 'TRX-DEMO-ONLINE-001'],
            [
                'user_id' => $buyer->id,
                'status' => OrderStatus::Open,
                'payment_status' => PaymentStatus::Unpaid,
                'payment_method' => PaymentMethod::Cash,
                'payment_proof_path' => null,
                'payment_confirmed_at' => null,
                'payment_confirmed_by' => null,
                'payment_rejection_reason' => null,
                'total_price' => 10_000,
                'pickup_method' => 'pickup',
                'pickup_location' => null,
            ],
        );
        OrderItem::query()->updateOrCreate(
            [
                'order_id' => $unpaidOrder->id,
                'product_id' => $pulpen->id,
            ],
            [
                'product_name' => $pulpen->name,
                'price' => $pulpen->price,
                'quantity' => 2,
                'subtotal' => 10_000,
                'status' => OrderItemStatus::Pending,
                'payment_status' => PaymentStatus::Unpaid,
                'payment_method' => PaymentMethod::Cash,
                'payment_confirmed_at' => null,
                'payment_confirmed_by' => null,
                'payment_rejection_reason' => null,
                'is_pre_order' => false,
                'pre_order_estimate_days' => null,
                'pre_order_deadline' => null,
                'pre_order_min_quantity' => null,
                'pre_order_note' => null,
            ],
        );

        $sentOrder = Order::query()->updateOrCreate(
            ['code' => 'TRX-DEMO-SENT-001'],
            [
                'user_id' => $buyer->id,
                'status' => OrderStatus::Open,
                'payment_status' => PaymentStatus::Paid,
                'payment_method' => PaymentMethod::Cash,
                'payment_proof_path' => null,
                'payment_confirmed_at' => now(),
                'payment_confirmed_by' => $seller->id,
                'payment_rejection_reason' => null,
                'total_price' => 5_000,
                'pickup_method' => 'pickup',
                'pickup_location' => null,
            ],
        );
        OrderItem::query()->updateOrCreate(
            [
                'order_id' => $sentOrder->id,
                'product_id' => $pulpen->id,
            ],
            [
                'product_name' => $pulpen->name,
                'price' => $pulpen->price,
                'quantity' => 1,
                'subtotal' => 5_000,
                'status' => OrderItemStatus::Sent,
                'payment_status' => PaymentStatus::Paid,
                'payment_method' => PaymentMethod::Cash,
                'payment_confirmed_at' => now(),
                'payment_confirmed_by' => $seller->id,
                'payment_rejection_reason' => null,
                'is_pre_order' => false,
                'pre_order_estimate_days' => null,
                'pre_order_deadline' => null,
                'pre_order_min_quantity' => null,
                'pre_order_note' => null,
            ],
        );
    }

    private function seedPosSale(User $picket, UpJurusanConsignment $consignment): void
    {
        $sale = UpJurusanPosSale::query()->updateOrCreate(
            ['code' => 'TRX-DEMO-POS-001'],
            [
                'up_jurusan_id' => $consignment->up_jurusan_id,
                'user_id' => $picket->id,
                'total_quantity' => 2,
                'total_amount' => 12_000,
            ],
        );

        UpJurusanStockMovement::query()->updateOrCreate(
            [
                'up_jurusan_consignment_id' => $consignment->id,
                'up_jurusan_pos_sale_id' => $sale->id,
                'type' => 'out',
            ],
            [
                'product_id' => null,
                'order_id' => null,
                'user_id' => $picket->id,
                'quantity' => 2,
                'unit_price' => 6_000,
                'gross_amount' => 12_000,
                'commission_amount' => 1_200,
                'seller_amount' => 10_800,
                'note' => 'Demo penjualan POS sebelum laporan harian dikirim.',
            ],
        );
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
            ['name' => 'Merchandise', 'slug' => 'merchandise'],
        ];
    }

    /**
     * @return array<int, array{key: string, owner: string, category_slug: string, name: string, slug: string, description: string, price: int, stock: int, sales_method: ProductSalesMethod, fulfillment_type: ProductFulfillmentType, pre_order_estimate_days: int|null, pre_order_deadline: string|null, pre_order_min_quantity: int|null, pre_order_note: string|null, status: ProductStatus}>
     */
    private function products(): array
    {
        return [
            [
                'key' => 'pulpen',
                'owner' => 'seller',
                'category_slug' => 'alat-tulis',
                'name' => 'Pulpen Gel Hitam',
                'slug' => 'pulpen-gel-hitam',
                'description' => 'Pulpen gel hitam untuk kebutuhan catatan harian siswa.',
                'price' => 5_000,
                'stock' => 40,
                'sales_method' => ProductSalesMethod::SelfManaged,
                'fulfillment_type' => ProductFulfillmentType::ReadyStock,
                'pre_order_estimate_days' => null,
                'pre_order_deadline' => null,
                'pre_order_min_quantity' => null,
                'pre_order_note' => null,
                'status' => ProductStatus::Approved,
            ],
            [
                'key' => 'stiker',
                'owner' => 'seller',
                'category_slug' => 'merchandise',
                'name' => 'Stiker Kelas Custom',
                'slug' => 'stiker-kelas-custom',
                'description' => 'Stiker kelas custom yang diproduksi setelah pesanan terkumpul.',
                'price' => 12_000,
                'stock' => 0,
                'sales_method' => ProductSalesMethod::SelfManaged,
                'fulfillment_type' => ProductFulfillmentType::PreOrder,
                'pre_order_estimate_days' => 7,
                'pre_order_deadline' => '2026-07-15',
                'pre_order_min_quantity' => 10,
                'pre_order_note' => 'Produksi dimulai setelah minimal 10 pesanan terkumpul.',
                'status' => ProductStatus::Approved,
            ],
            [
                'key' => 'keripik',
                'owner' => 'seller',
                'category_slug' => 'makanan-ringan',
                'name' => 'Keripik Singkong Pedas',
                'slug' => 'keripik-singkong-pedas',
                'description' => 'Camilan keripik singkong pedas dalam kemasan kecil.',
                'price' => 8_000,
                'stock' => 25,
                'sales_method' => ProductSalesMethod::SelfManaged,
                'fulfillment_type' => ProductFulfillmentType::ReadyStock,
                'pre_order_estimate_days' => null,
                'pre_order_deadline' => null,
                'pre_order_min_quantity' => null,
                'pre_order_note' => null,
                'status' => ProductStatus::Pending,
            ],
            [
                'key' => 'kaos',
                'owner' => 'up_jurusan',
                'category_slug' => 'merchandise',
                'name' => 'Kaos RPL',
                'slug' => 'kaos-rpl',
                'description' => 'Kaos jurusan RPL untuk kegiatan kelas dan event sekolah.',
                'price' => 75_000,
                'stock' => 10,
                'sales_method' => ProductSalesMethod::UpJurusan,
                'fulfillment_type' => ProductFulfillmentType::ReadyStock,
                'pre_order_estimate_days' => null,
                'pre_order_deadline' => null,
                'pre_order_min_quantity' => null,
                'pre_order_note' => null,
                'status' => ProductStatus::Approved,
            ],
            [
                'key' => 'risol',
                'owner' => 'seller',
                'category_slug' => 'makanan-ringan',
                'name' => 'Risol Mayo Titipan',
                'slug' => 'risol-mayo-titipan',
                'description' => 'Risol mayo titipan seller yang dijual melalui POS UP RPL.',
                'price' => 6_000,
                'stock' => 0,
                'sales_method' => ProductSalesMethod::UpJurusan,
                'fulfillment_type' => ProductFulfillmentType::ReadyStock,
                'pre_order_estimate_days' => null,
                'pre_order_deadline' => null,
                'pre_order_min_quantity' => null,
                'pre_order_note' => null,
                'status' => ProductStatus::Approved,
            ],
        ];
    }
}
