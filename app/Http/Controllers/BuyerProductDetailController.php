<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class BuyerProductDetailController extends Controller
{
    public function __invoke(Product $product): Response
    {
        abort_unless($product->status === ProductStatus::Approved, 404);

        $product->load([
            'category:id,name,slug',
            'seller:id,name',
            'upJurusan:id,name',
            'upJurusanConsignments.upJurusan:id,name',
        ]);

        $pickupPlace = $product->upJurusanConsignments
            ->first()
            ?->upJurusan;

        return Inertia::render('catalog/show', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'price' => $product->price,
                'stock' => $product->availableStock(),
                'image' => $product->image,
                'seller' => $product->seller ? [
                    'id' => $product->seller->id,
                    'name' => $product->seller->name,
                ] : null,
                'owner' => $this->ownerPayload($product),
                'category' => [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ],
                'pickup_place' => $pickupPlace ? [
                    'id' => $pickupPlace->id,
                    'name' => $pickupPlace->name,
                ] : null,
            ],
        ]);
    }

    /**
     * @return array{id: int, name: string, type: string}
     */
    private function ownerPayload(Product $product): array
    {
        if ($product->upJurusan) {
            return [
                'id' => $product->upJurusan->id,
                'name' => $product->upJurusan->name,
                'type' => 'up_jurusan',
            ];
        }

        return [
            'id' => $product->seller->id,
            'name' => $product->seller->name,
            'type' => 'seller',
        ];
    }
}
