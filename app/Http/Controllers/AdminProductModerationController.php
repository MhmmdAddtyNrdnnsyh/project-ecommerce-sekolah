<?php

namespace App\Http\Controllers;

use App\Enums\ProductSalesMethod;
use App\Enums\ProductStatus;
use App\Http\Requests\Admin\RejectProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminProductModerationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/products/moderation', [
            'products' => Product::query()
                ->with(['category:id,name,slug', 'seller:id,name,email'])
                ->where('status', ProductStatus::Pending)
                ->where('sales_method', ProductSalesMethod::SelfManaged)
                ->whereNotNull('seller_id')
                ->oldest()
                ->get(['id', 'seller_id', 'category_id', 'name', 'slug', 'description', 'price', 'stock', 'status', 'created_at'])
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'description' => $product->description,
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'submitted_at' => $product->created_at?->diffForHumans() ?? '-',
                    'seller' => [
                        'id' => $product->seller->id,
                        'name' => $product->seller->name,
                        'email' => $product->seller->email,
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

    public function approve(Product $product): RedirectResponse
    {
        $this->ensurePending($product);

        $product->update([
            'status' => ProductStatus::Approved,
            'rejection_reason' => null,
        ]);

        return back();
    }

    public function reject(RejectProductRequest $request, Product $product): RedirectResponse
    {
        $this->ensurePending($product);

        $reason = trim($request->string('reason')->toString());

        $product->update([
            'status' => ProductStatus::Rejected,
            'rejection_reason' => $reason === '' ? null : $reason,
        ]);

        return back();
    }

    private function ensurePending(Product $product): void
    {
        abort_unless(
            $product->status === ProductStatus::Pending
            && $product->sales_method === ProductSalesMethod::SelfManaged,
            404,
        );
    }
}
