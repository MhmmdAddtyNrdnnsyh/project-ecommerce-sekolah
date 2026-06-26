<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminProductController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(ProductStatus::class)],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Product::query()
            ->with(['category:id,name,slug', 'seller:id,name,email', 'upJurusan:id,name'])
            ->withCount('orderItems');

        if ($search = $validated['q'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhereHas('seller', fn ($seller) => $seller->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $validated['status'] ?? null) {
            $query->where('status', $status);
        }

        if ($categoryId = $validated['category_id'] ?? null) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->latest()->paginate(10)->withQueryString();

        /** @var LengthAwarePaginator<int, Product> $products */

        return Inertia::render('admin/products/index', [
            'products' => $products->through(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->price,
                'stock' => $product->stock,
                'order_items_count' => $product->order_items_count,
                'status' => [
                    'code' => $product->status->value,
                    'label' => $product->status->label(),
                ],
                'seller' => $product->seller ? [
                    'id' => $product->seller->id,
                    'name' => $product->seller->name,
                    'email' => $product->seller->email,
                ] : [
                    'id' => $product->upJurusan->id,
                    'name' => $product->upJurusan->name,
                    'email' => 'UP Jurusan',
                ],
                'category' => [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ],
            ]),
            'categories' => Category::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ])
                ->all(),
            'statuses' => collect(ProductStatus::cases())
                ->map(fn (ProductStatus $status) => [
                    'code' => $status->value,
                    'name' => $status->label(),
                ])
                ->all(),
            'filters' => [
                'q' => $validated['q'] ?? '',
                'status' => $validated['status'] ?? '',
                'category_id' => $validated['category_id'] ?? '',
            ],
        ]);
    }
}
