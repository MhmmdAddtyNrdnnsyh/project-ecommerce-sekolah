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

        $product->load(['category:id,name,slug', 'seller:id,name']);

        return Inertia::render('catalog/show', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'price' => $product->price,
                'stock' => $product->stock,
                'image' => $product->image,
                'seller' => [
                    'id' => $product->seller->id,
                    'name' => $product->seller->name,
                ],
                'category' => [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ],
            ],
        ]);
    }
}
