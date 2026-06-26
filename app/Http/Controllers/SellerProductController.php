<?php

namespace App\Http\Controllers;

use App\Enums\ProductSalesMethod;
use App\Enums\ProductStatus;
use App\Enums\UpJurusanConsignmentStatus;
use App\Http\Requests\Seller\StoreProductRequest;
use App\Http\Requests\Seller\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SellerProductController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $seller */
        $seller = $request->user();

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(ProductStatus::class)],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'stock' => ['nullable', Rule::in(['all', 'low', 'out'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Product::query()
            ->with('category:id,name,slug')
            ->where('seller_id', $seller->id);

        if ($search = $validated['q'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($status = $validated['status'] ?? null) {
            $query->where('status', $status);
        }

        if ($categoryId = $validated['category_id'] ?? null) {
            $query->where('category_id', $categoryId);
        }

        if ($stock = $validated['stock'] ?? null) {
            match ($stock) {
                'out' => $query->where('stock', 0),
                'low' => $query->where('stock', '>', 0)->where('stock', '<=', Product::LOW_STOCK_THRESHOLD),
                default => null,
            };
        }

        $perPage = 10;

        $products = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('seller/products/index', [
            'products' => $products->through(fn (Product $product) => [
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
            ]),
            'categories' => $this->categoryOptions(),
            'filters' => [
                'q' => $validated['q'] ?? '',
                'status' => $validated['status'] ?? '',
                'category_id' => $validated['category_id'] ?? '',
                'stock' => $validated['stock'] ?? '',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('seller/products/create', [
            'categories' => $this->categoryOptions(),
            'upJurusans' => $this->upJurusanOptions(),
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

        $salesMethod = ProductSalesMethod::from(
            $request->input('sales_method', ProductSalesMethod::SelfManaged->value),
        );
        $status = ProductStatus::from($request->input('status', ProductStatus::Pending->value));

        DB::transaction(function () use ($request, $seller, $imagePath, $salesMethod, $status) {
            $product = Product::query()->create([
                'seller_id' => $seller->id,
                'category_id' => $request->integer('category_id'),
                'name' => $request->string('name')->toString(),
                'slug' => $this->uniqueSlug($request->string('name')->toString()),
                'description' => $request->string('description')->toString(),
                'price' => $request->integer('price'),
                'stock' => $salesMethod === ProductSalesMethod::UpJurusan ? 0 : $request->integer('stock'),
                'sales_method' => $salesMethod,
                'status' => $status,
                'image' => $imagePath,
            ]);

            if ($salesMethod === ProductSalesMethod::UpJurusan && $status === ProductStatus::Pending) {
                UpJurusanConsignment::query()->create([
                    'seller_id' => $seller->id,
                    'product_id' => $product->id,
                    'up_jurusan_id' => $request->integer('up_jurusan_id'),
                    'requested_quantity' => $request->integer('requested_quantity'),
                    'status' => UpJurusanConsignmentStatus::PendingApproval,
                ]);
            }
        });

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
            'status' => $product->status === ProductStatus::Approved
                ? ProductStatus::Pending
                : $product->status,
            'image' => $imagePath,
        ]);

        return to_route('seller.products.index');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeOwner($request, $product);

        if ($product->orderItems()->exists()) {
            throw ValidationException::withMessages([
                'product' => 'Produk tidak dapat dihapus karena sudah memiliki riwayat pesanan.',
            ])->redirectTo(route('seller.products.index'));
        }

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return to_route('seller.products.index')
            ->with('success', 'Produk berhasil dihapus.');
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

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function upJurusanOptions(): array
    {
        return UpJurusan::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (UpJurusan $upJurusan) => [
                'id' => $upJurusan->id,
                'name' => $upJurusan->name,
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
