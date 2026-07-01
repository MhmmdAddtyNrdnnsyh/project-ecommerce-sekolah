<?php

namespace App\Http\Controllers;

use App\Enums\ProductFulfillmentType;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BuyerCatalogController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $category = trim((string) $request->query('category', ''));
        $category = $category === 'all' ? '' : $category;

        return Inertia::render('catalog/index', [
            'filters' => [
                'search' => $search,
                'category' => $category,
            ],
            'categories' => Category::query()
                ->whereHas('products', fn ($query) => $query
                    ->where('status', ProductStatus::Approved)
                    ->where(fn ($query) => $query
                        ->where('fulfillment_type', ProductFulfillmentType::PreOrder)
                        ->orWhere('stock', '>', 0)
                        ->orWhereHas('upJurusanConsignments', fn ($query) => $query->whereColumn('received_quantity', '>', 'sold_quantity'))))
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ])
                ->all(),
            'products' => Product::query()
                ->with(['category:id,name,slug', 'seller:id,name', 'upJurusan:id,name'])
                ->with('upJurusanConsignments:id,product_id,received_quantity,sold_quantity')
                ->where('status', ProductStatus::Approved)
                ->where(fn ($query) => $query
                    ->where('fulfillment_type', ProductFulfillmentType::PreOrder)
                    ->orWhere('stock', '>', 0)
                    ->orWhereHas('upJurusanConsignments', fn ($query) => $query->whereColumn('received_quantity', '>', 'sold_quantity')))
                ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                }))
                ->when($category !== '', fn ($query) => $query->whereHas(
                    'category',
                    fn ($query) => $query->where('slug', $category),
                ))
                ->latest()
                ->get(['id', 'seller_id', 'up_jurusan_id', 'category_id', 'name', 'slug', 'description', 'price', 'stock', 'sales_method', 'fulfillment_type', 'pre_order_estimate_days', 'pre_order_deadline', 'pre_order_min_quantity', 'pre_order_note', 'image'])
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'description' => $product->description,
                    'price' => $product->price,
                    'stock' => $product->availableStock(),
                    'is_pre_order' => $product->isPreOrder(),
                    'fulfillment_type' => [
                        'code' => $product->fulfillment_type->value,
                        'label' => $product->fulfillment_type->label(),
                    ],
                    'pre_order_estimate_days' => $product->pre_order_estimate_days,
                    'pre_order_deadline' => $product->pre_order_deadline?->toDateString(),
                    'pre_order_min_quantity' => $product->pre_order_min_quantity,
                    'pre_order_note' => $product->pre_order_note,
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
                ])
                ->values()
                ->all(),
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
