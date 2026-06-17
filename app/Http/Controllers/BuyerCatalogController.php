<?php

namespace App\Http\Controllers;

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
                    ->where('stock', '>', 0))
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ])
                ->all(),
            'products' => Product::query()
                ->with(['category:id,name,slug', 'seller:id,name'])
                ->where('status', ProductStatus::Approved)
                ->where('stock', '>', 0)
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
                ->get(['id', 'seller_id', 'category_id', 'name', 'slug', 'description', 'price', 'stock', 'image'])
                ->map(fn (Product $product) => [
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
                ])
                ->values()
                ->all(),
        ]);
    }
}
