<?php

namespace App\Http\Middleware;

use App\Enums\OrderItemStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\CartItem;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    private const HEADER_NOTIFICATION_LIMIT = 50;

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'adminHeader' => fn () => $this->adminHeader($request),
            'buyerHeader' => fn () => $this->buyerHeader($request),
            'sellerHeader' => fn () => $this->sellerHeader($request),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * @return array{cartItemsCount: int}|null
     */
    private function buyerHeader(Request $request): ?array
    {
        /** @var User|null $buyer */
        $buyer = $request->user();

        if ($buyer?->role !== UserRole::Buyer) {
            return null;
        }

        return [
            'cartItemsCount' => (int) CartItem::query()
                ->where('user_id', $buyer->id)
                ->count(),
        ];
    }

    /**
     * @return array{notifications: array<int, array{type: string, title: string, description: string, href: string}>, supportEmail: string|null}|null
     */
    private function adminHeader(Request $request): ?array
    {
        /** @var User|null $admin */
        $admin = $request->user();

        if ($admin?->role !== UserRole::Admin) {
            return null;
        }

        $products = Product::query()
            ->with('seller:id,name')
            ->where('status', ProductStatus::Pending)
            ->whereNotNull('seller_id')
            ->oldest()
            ->limit(self::HEADER_NOTIFICATION_LIMIT)
            ->get(['id', 'seller_id', 'name'])
            ->map(fn (Product $product) => [
                'type' => 'product',
                'title' => $product->name,
                'description' => 'Menunggu moderasi dari '.$product->seller->name,
                'href' => route('admin.products.moderation.index', absolute: false),
            ]);

        return [
            'notifications' => $products->values()->all(),
            'supportEmail' => config('mail.from.address'),
        ];
    }

    /**
     * @return array{notifications: array<int, array{type: string, title: string, description: string, href: string}>, supportEmail: string|null}|null
     */
    private function sellerHeader(Request $request): ?array
    {
        /** @var User|null $seller */
        $seller = $request->user();

        if ($seller?->role !== UserRole::Seller) {
            return null;
        }

        $orders = OrderItem::query()
            ->whereHas('product', fn ($query) => $query->where('seller_id', $seller->id))
            ->where('status', OrderItemStatus::Pending)
            ->latest()
            ->limit(self::HEADER_NOTIFICATION_LIMIT)
            ->get()
            ->map(fn (OrderItem $item) => [
                'type' => 'order',
                'title' => "Pesanan #{$item->order_id}",
                'description' => $item->product_name.' menunggu diproses',
                'href' => route('seller.orders.show', $item, absolute: false),
            ]);

        $stock = Product::query()
            ->where('seller_id', $seller->id)
            ->where('stock', '<=', Product::LOW_STOCK_THRESHOLD)
            ->orderBy('stock')
            ->limit(self::HEADER_NOTIFICATION_LIMIT)
            ->get(['id', 'name', 'stock'])
            ->map(fn (Product $product) => [
                'type' => 'stock',
                'title' => $product->name,
                'description' => $product->stock === 0 ? 'Stok habis' : "Stok tersisa {$product->stock}",
                'href' => route('seller.inventory.index', ['q' => $product->name], absolute: false),
            ]);

        return [
            'notifications' => $orders->concat($stock)->values()->all(),
            'supportEmail' => config('mail.from.address'),
        ];
    }
}
