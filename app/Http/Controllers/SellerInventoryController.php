<?php

namespace App\Http\Controllers;

use App\Enums\ProductSalesMethod;
use App\Http\Requests\Seller\UpdateInventoryRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SellerInventoryController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $seller */
        $seller = $request->user();

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
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

        if ($stock = $validated['stock'] ?? null) {
            match ($stock) {
                'out' => $query->where('stock', 0),
                'low' => $query->where('stock', '>', 0)->where('stock', '<=', Product::LOW_STOCK_THRESHOLD),
                default => null,
            };
        }

        $perPage = 10;

        $products = $query->latest()->paginate($perPage)->withQueryString();

        $totalProducts = Product::query()->where('seller_id', $seller->id)->count();
        $lowStockCount = Product::query()
            ->where('seller_id', $seller->id)
            ->where('stock', '>', 0)
            ->where('stock', '<=', Product::LOW_STOCK_THRESHOLD)
            ->count();
        $outOfStockCount = Product::query()
            ->where('seller_id', $seller->id)
            ->where('stock', 0)
            ->count();

        return Inertia::render('seller/inventory/index', [
            'products' => $products->through(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => $product->image,
                'status' => [
                    'code' => $product->status->value,
                    'label' => $product->status->label(),
                ],
                'stock' => $product->stock,
                'category' => [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ],
                'is_low_stock' => $product->stock > 0 && $product->stock <= Product::LOW_STOCK_THRESHOLD,
                'is_out_of_stock' => $product->stock === 0,
            ]),
            'summary' => [
                'total' => $totalProducts,
                'low_stock' => $lowStockCount,
                'out_of_stock' => $outOfStockCount,
            ],
            'filters' => [
                'q' => $validated['q'] ?? '',
                'stock' => $validated['stock'] ?? '',
            ],
        ]);
    }

    public function update(UpdateInventoryRequest $request, Product $product): RedirectResponse
    {
        /** @var User $seller */
        $seller = $request->user();

        if ($product->seller_id !== $seller->id) {
            abort(403);
        }

        if ($product->sales_method === ProductSalesMethod::UpJurusan) {
            abort(403);
        }

        $product->update([
            'stock' => $request->integer('stock'),
        ]);

        return to_route('seller.inventory.index')
            ->with('success', 'Stok produk berhasil diperbarui.');
    }
}
