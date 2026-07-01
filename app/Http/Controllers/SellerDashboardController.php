<?php

namespace App\Http\Controllers;

use App\Enums\OrderItemStatus;
use App\Enums\ProductStatus;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\UpJurusanStockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SellerDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $seller */
        $seller = $request->user();

        return Inertia::render('seller/dashboard', [
            'dashboard' => [
                'stats' => $this->stats($seller),
                'salesData' => $this->salesData($seller),
                'salesChannelData' => $this->salesChannelData($seller),
                'orderMixData' => $this->orderMixData($seller),
                'orders' => $this->latestOrders($seller),
                'topProducts' => $this->topProducts($seller),
                'stockAlerts' => $this->stockAlerts($seller),
                'tasks' => $this->tasks($seller),
            ],
        ]);
    }

    /**
     * @return array<int, array{label: string, value: string, context: string, trend: string, tone: string, icon: string}>
     */
    private function stats(User $seller): array
    {
        $now = now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $revenue = fn ($start, $end): int => (int) OrderItem::query()
            ->whereHas('product', fn ($q) => $q->where('seller_id', $seller->id))
            ->whereBetween('created_at', [$start, $end])
            ->sum('subtotal');
        $offlineRevenue = fn ($start, $end): int => (int) UpJurusanStockMovement::query()
            ->where('type', 'out')
            ->whereHas('consignment', fn ($q) => $q->where('seller_id', $seller->id))
            ->whereBetween('created_at', [$start, $end])
            ->sum('seller_amount');

        $currentMonthRevenue = $revenue($currentMonthStart, $currentMonthEnd) + $offlineRevenue($currentMonthStart, $currentMonthEnd);
        $lastMonthRevenue = $revenue($lastMonthStart, $lastMonthEnd) + $offlineRevenue($lastMonthStart, $lastMonthEnd);

        $revenueTrend = match (true) {
            $lastMonthRevenue > 0 => round(($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue * 100).'%',
            $currentMonthRevenue > 0 => '100%',
            default => '0%',
        };

        $incomingOrders = OrderItem::query()
            ->whereHas('product', fn ($q) => $q->where('seller_id', $seller->id))
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->distinct()
            ->count('order_id');
        $incomingOrders += UpJurusanStockMovement::query()
            ->where('type', 'out')
            ->whereHas('consignment', fn ($q) => $q->where('seller_id', $seller->id))
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->count();

        $activeProducts = Product::query()
            ->where('seller_id', $seller->id)
            ->where('status', ProductStatus::Approved)
            ->count();

        $lowStockProducts = Product::query()
            ->where('seller_id', $seller->id)
            ->whereRaw(Product::REAL_STOCK_SQL.' > 0')
            ->whereRaw(Product::REAL_STOCK_SQL.' <= ?', [Product::LOW_STOCK_THRESHOLD])
            ->count();

        return [
            [
                'label' => 'Omzet Bulan Ini',
                'value' => 'Rp '.number_format((float) $currentMonthRevenue, 0, ',', '.'),
                'context' => $currentMonthRevenue > 0 ? 'Pendapatan bulan ini' : 'Belum ada transaksi bulan ini',
                'trend' => $revenueTrend,
                'tone' => 'blue',
                'icon' => 'badgeDollarSign',
            ],
            [
                'label' => 'Pesanan Masuk',
                'value' => (string) $incomingOrders,
                'context' => $incomingOrders > 0 ? 'Order bulan ini' : 'Belum ada order bulan ini',
                'trend' => "{$incomingOrders} order",
                'tone' => 'emerald',
                'icon' => 'shoppingCart',
            ],
            [
                'label' => 'Produk Aktif',
                'value' => (string) $activeProducts,
                'context' => $activeProducts > 0 ? 'Produk yang aktif dijual' : 'Belum ada produk aktif',
                'trend' => $activeProducts > 0 ? "{$activeProducts} item" : '0 item',
                'tone' => 'amber',
                'icon' => 'package',
            ],
            [
                'label' => 'Stok Rendah',
                'value' => (string) $lowStockProducts,
                'context' => $lowStockProducts > 0 ? 'Perlu segera diisi' : 'Stok produk aman',
                'trend' => "{$lowStockProducts} item",
                'tone' => $lowStockProducts > 0 ? 'rose' : 'emerald',
                'icon' => 'boxes',
            ],
        ];
    }

    /**
     * @return array<int, array{day: string, sales: int, orders: int}>
     */
    private function salesData(User $seller): array
    {
        $start = now()->subDays(6)->startOfDay();
        $end = now()->endOfDay();

        /** @var Collection<string, object{total_sales: int, total_orders: int}> $totals */
        $totals = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.seller_id', $seller->id)
            ->whereBetween('order_items.created_at', [$start, $end])
            ->selectRaw('DATE(order_items.created_at) as sale_date, COALESCE(SUM(order_items.subtotal), 0) as total_sales, COUNT(DISTINCT order_items.order_id) as total_orders')
            ->groupByRaw('DATE(order_items.created_at)')
            ->get()
            ->keyBy('sale_date');
        /** @var Collection<string, object{total_sales: int, total_orders: int}> $offlineTotals */
        $offlineTotals = DB::table('up_jurusan_stock_movements')
            ->join('up_jurusan_consignments', 'up_jurusan_stock_movements.up_jurusan_consignment_id', '=', 'up_jurusan_consignments.id')
            ->where('up_jurusan_consignments.seller_id', $seller->id)
            ->where('up_jurusan_stock_movements.type', 'out')
            ->whereBetween('up_jurusan_stock_movements.created_at', [$start, $end])
            ->selectRaw('DATE(up_jurusan_stock_movements.created_at) as sale_date, COALESCE(SUM(up_jurusan_stock_movements.seller_amount), 0) as total_sales, COUNT(*) as total_orders')
            ->groupByRaw('DATE(up_jurusan_stock_movements.created_at)')
            ->get()
            ->keyBy('sale_date');

        return collect(range(6, 0))
            ->map(function (int $daysAgo) use ($totals, $offlineTotals) {
                $date = now()->subDays($daysAgo);
                $dayStats = $totals->get($date->toDateString());
                $offlineDayStats = $offlineTotals->get($date->toDateString());

                return [
                    'day' => $date->translatedFormat('D'),
                    'sales' => (int) ($dayStats->total_sales ?? 0) + (int) ($offlineDayStats->total_sales ?? 0),
                    'orders' => (int) ($dayStats->total_orders ?? 0) + (int) ($offlineDayStats->total_orders ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{channel: string, label: string, orders: int, revenue: int, fill: string}>
     */
    private function salesChannelData(User $seller): array
    {
        $start = now()->subDays(29)->startOfDay();
        $end = now()->endOfDay();

        $onlineOrders = OrderItem::query()
            ->whereHas('product', fn ($q) => $q->where('seller_id', $seller->id))
            ->whereBetween('created_at', [$start, $end]);
        $offlineOrders = UpJurusanStockMovement::query()
            ->where('type', 'out')
            ->whereHas('consignment', fn ($q) => $q->where('seller_id', $seller->id))
            ->whereBetween('created_at', [$start, $end]);

        return [
            [
                'channel' => 'online',
                'label' => 'Website',
                'orders' => (clone $onlineOrders)->distinct()->count('order_id'),
                'revenue' => (int) (clone $onlineOrders)->sum('subtotal'),
                'fill' => '#2563eb',
            ],
            [
                'channel' => 'offline',
                'label' => 'POS UP Jurusan',
                'orders' => (clone $offlineOrders)->count(),
                'revenue' => (int) (clone $offlineOrders)->sum('seller_amount'),
                'fill' => '#10b981',
            ],
        ];
    }

    /**
     * @return array<int, array{status: string, label: string, value: int, fill: string}>
     */
    private function orderMixData(User $seller): array
    {
        $counts = OrderItem::query()
            ->whereHas('product', fn ($q) => $q->where('seller_id', $seller->id))
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            [
                'status' => 'pending',
                'label' => OrderItemStatus::Pending->label(),
                'value' => (int) ($counts[OrderItemStatus::Pending->value] ?? 0),
                'fill' => 'var(--color-pending)',
            ],
            [
                'status' => 'packed',
                'label' => OrderItemStatus::Packed->label(),
                'value' => (int) ($counts[OrderItemStatus::Packed->value] ?? 0),
                'fill' => 'var(--color-packed)',
            ],
            [
                'status' => 'sent',
                'label' => OrderItemStatus::Sent->label(),
                'value' => (int) ($counts[OrderItemStatus::Sent->value] ?? 0),
                'fill' => 'var(--color-sent)',
            ],
            [
                'status' => 'completed',
                'label' => OrderItemStatus::Completed->label(),
                'value' => (int) ($counts[OrderItemStatus::Completed->value] ?? 0),
                'fill' => 'var(--color-completed)',
            ],
        ];
    }

    /**
     * @return array<int, array{id: int, source: string, code: string, order_id: int|string, buyer: string, product: string, amount: string, status: string, time: string, meta: string|null, gross_amount: string|null, commission_amount: string|null}>
     */
    private function latestOrders(User $seller): array
    {
        $onlineOrders = OrderItem::query()
            ->with(['order:id,code,user_id', 'order.user:id,name', 'product:id,seller_id'])
            ->whereHas('product', fn ($q) => $q->where('seller_id', $seller->id))
            ->latest('order_items.created_at')
            ->limit(5)
            ->get()
            ->map(fn (OrderItem $item): array => [
                'id' => $item->id,
                'source' => 'online',
                'code' => $item->order->code ?? "TRX-{$item->order_id}",
                'order_id' => $item->order_id,
                'buyer' => $item->order->user->name,
                'product' => $item->product_name,
                'amount' => 'Rp '.number_format($item->subtotal, 0, ',', '.'),
                'meta' => null,
                'gross_amount' => null,
                'commission_amount' => null,
                'status' => $item->status->value,
                'time' => $item->created_at?->translatedFormat('d M, H:i') ?? '',
                'created_at' => $item->created_at->timestamp,
            ]);

        $offlineOrders = UpJurusanStockMovement::query()
            ->with([
                'user:id,name',
                'consignment.product:id,name',
                'consignment.upJurusan:id,name',
                'posSale:id,code',
            ])
            ->where('type', 'out')
            ->whereHas('consignment', fn ($q) => $q->where('seller_id', $seller->id))
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (UpJurusanStockMovement $movement): array => [
                'id' => $movement->id,
                'source' => 'offline',
                'code' => $movement->posSale->code ?? "TRX-OFF-{$movement->id}",
                'order_id' => $movement->posSale->code ?? $movement->id,
                'buyer' => 'Pembeli offline',
                'product' => $movement->consignment->product->name,
                'amount' => 'Rp '.number_format($movement->seller_amount, 0, ',', '.'),
                'meta' => $movement->consignment->upJurusan->name.' • '.$movement->user->name,
                'gross_amount' => 'Rp '.number_format($movement->gross_amount, 0, ',', '.'),
                'commission_amount' => 'Rp '.number_format($movement->commission_amount, 0, ',', '.'),
                'status' => OrderItemStatus::Sent->value,
                'time' => $movement->created_at?->translatedFormat('d M, H:i') ?? '',
                'created_at' => $movement->created_at->timestamp,
            ]);

        return collect($onlineOrders->all())
            ->merge($offlineOrders)
            ->sortByDesc('created_at')
            ->take(5)
            ->map(fn (array $item): array => [
                'id' => $item['id'],
                'source' => $item['source'],
                'code' => $item['code'],
                'order_id' => $item['order_id'],
                'buyer' => $item['buyer'],
                'product' => $item['product'],
                'amount' => $item['amount'],
                'meta' => $item['meta'],
                'gross_amount' => $item['gross_amount'],
                'commission_amount' => $item['commission_amount'],
                'status' => $item['status'],
                'time' => $item['time'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{name: string, category: string, sold: string, revenue: string, icon: string}>
     */
    private function topProducts(User $seller): array
    {
        /** @var Collection<int, object{product_name: string, category_name: string|null, total_qty: int, total_revenue: int}> $products */
        $products = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('products.seller_id', $seller->id)
            ->select(
                'products.name as product_name',
                'categories.name as category_name',
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_qty'),
                DB::raw('COALESCE(SUM(order_items.subtotal), 0) as total_revenue'),
            )
            ->groupBy('products.id', 'products.name', 'categories.name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return $products
            ->map(fn (object $item): array => [
                'name' => $item->product_name,
                'category' => $item->category_name ?? '',
                'sold' => "{$item->total_qty} terjual",
                'revenue' => 'Rp '.number_format($item->total_revenue, 0, ',', '.'),
                'icon' => 'package',
            ])
            ->all();
    }

    /**
     * @return array<int, array{product: string, sku: string, stock: string, tone: string, icon: string}>
     */
    private function stockAlerts(User $seller): array
    {
        return Product::query()
            ->select('products.*')
            ->selectRaw(Product::REAL_STOCK_SQL.' as real_stock')
            ->where('seller_id', $seller->id)
            ->whereRaw(Product::REAL_STOCK_SQL.' <= ?', [Product::LOW_STOCK_THRESHOLD])
            ->orderByRaw(Product::REAL_STOCK_SQL)
            ->orderBy('id')
            ->limit(5)
            ->get()
            ->map(function (Product $product): array {
                $realStock = (int) $product->getAttribute('real_stock');

                return [
                    'product' => $product->name,
                    'sku' => '#'.$product->id,
                    'stock' => (string) $realStock,
                    'tone' => $realStock === 0 ? 'danger' : 'warning',
                    'icon' => 'package',
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{title: string, detail: string, action: string, icon: string, tone: string}>
     */
    private function tasks(User $seller): array
    {
        $activeProducts = Product::query()
            ->where('seller_id', $seller->id)
            ->where('status', ProductStatus::Approved)
            ->count();

        $pendingOrders = OrderItem::query()
            ->whereHas('product', fn ($q) => $q->where('seller_id', $seller->id))
            ->where('status', OrderItemStatus::Pending)
            ->count();

        $tasks = [];

        if ($activeProducts === 0) {
            $tasks[] = [
                'title' => 'Siapkan katalog produk pertama',
                'detail' => 'Tambahkan produk untuk mulai berjualan di platform.',
                'action' => 'Tambah produk',
                'icon' => 'package',
                'tone' => 'amber',
            ];
        } else {
            $tasks[] = [
                'title' => "Produk aktif: {$activeProducts}",
                'detail' => 'Produk Anda sudah aktif dan dapat dibeli pembeli.',
                'action' => 'Lihat produk',
                'icon' => 'packageCheck',
                'tone' => 'emerald',
            ];
        }

        if ($pendingOrders > 0) {
            $tasks[] = [
                'title' => "{$pendingOrders} pesanan perlu diproses",
                'detail' => 'Segera proses pesanan yang masuk.',
                'action' => 'Proses pesanan',
                'icon' => 'shoppingBag',
                'tone' => 'blue',
            ];
        } else {
            $tasks[] = [
                'title' => 'Toko siap menerima pesanan',
                'detail' => 'Toko Anda aktif dan siap menerima pesanan dari pembeli.',
                'action' => 'Lihat toko',
                'icon' => 'store',
                'tone' => 'blue',
            ];
        }

        $tasks[] = [
            'title' => 'Akun seller sudah siap digunakan',
            'detail' => 'Masuk sebagai '.$seller->name.' dan pantau data toko dari halaman ini.',
            'action' => 'Lihat profil',
            'icon' => 'store',
            'tone' => 'blue',
        ];

        return $tasks;
    }
}
