<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Http\Requests\Seller\StoreProductRequest;
use App\Http\Requests\Seller\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SellerProductController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $seller */
        $seller = $request->user();

        return Inertia::render('seller/products/index', [
            'products' => Product::query()
                ->with('category:id,name,slug')
                ->where('seller_id', $seller->id)
                ->latest()
                ->get(['id', 'seller_id', 'category_id', 'name', 'slug', 'price', 'stock', 'status'])
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'category' => [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                        'slug' => $product->category->slug,
                    ],
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'status' => [
                        'code' => $product->status->value,
                        'label' => $product->status->label(),
                    ],
                ])
                ->values()
                ->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('seller/products/create', [
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        /** @var User $seller */
        $seller = $request->user();
        $imagePath = null;
        $image = $request->file('image');

        if ($image instanceof UploadedFile) {
            $storedImage = $image->store('products', 'public');
            $imagePath = $storedImage === false ? null : $storedImage;
        }

        Product::query()->create([
            'seller_id' => $seller->id,
            'category_id' => $request->integer('category_id'),
            'name' => $request->string('name')->toString(),
            'slug' => $this->uniqueSlug($request->string('name')->toString()),
            'description' => $request->string('description')->toString(),
            'price' => $request->integer('price'),
            'stock' => $request->integer('stock'),
            'status' => ProductStatus::Pending,
            'image' => $imagePath,
        ]);

        return to_route('seller.products.index');
    }

    public function edit(Request $request, Product $product): Response
    {
        $this->authorizeOwner($request, $product);
        $product->load('category:id,name,slug');

        return Inertia::render('seller/products/edit', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'category_id' => $product->category_id,
                'description' => $product->description,
                'price' => $product->price,
                'stock' => $product->stock,
                'image' => $product->image,
                'status' => [
                    'code' => $product->status->value,
                    'label' => $product->status->label(),
                ],
            ],
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorizeOwner($request, $product);
        $imagePath = $product->image;
        $image = $request->file('image');

        if ($image instanceof UploadedFile) {
            $storedImage = $image->store('products', 'public');
            $imagePath = $storedImage === false ? $imagePath : $storedImage;
        }

        $product->update([
            'category_id' => $request->integer('category_id'),
            'name' => $request->string('name')->toString(),
            'slug' => $this->uniqueSlug($request->string('name')->toString(), $product),
            'description' => $request->string('description')->toString(),
            'price' => $request->integer('price'),
            'stock' => $request->integer('stock'),
            'status' => $product->status === ProductStatus::Approved
                ? ProductStatus::Pending
                : $product->status,
            'image' => $imagePath,
        ]);

        return to_route('seller.products.index');
    }

    /**
     * @return array<int, array{id: int, name: string, slug: string}>
     */
    private function categoryOptions(): array
    {
        return Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])
            ->values()
            ->all();
    }

    private function authorizeOwner(Request $request, Product $product): void
    {
        /** @var User $seller */
        $seller = $request->user();

        abort_unless($product->seller_id === $seller->id, 403);
    }

    private function uniqueSlug(string $name, ?Product $ignoredProduct = null): string
    {
        $baseSlug = Str::slug($name) ?: 'product';
        $slug = $baseSlug;
        $suffix = 2;

        while ($this->slugExists($slug, $ignoredProduct)) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?Product $ignoredProduct = null): bool
    {
        return Product::query()
            ->where('slug', $slug)
            ->when(
                $ignoredProduct,
                fn ($query) => $query->whereKeyNot($ignoredProduct->getKey()),
            )
            ->exists();
    }
}
