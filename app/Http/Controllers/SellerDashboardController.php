<?php

namespace App\Http\Controllers;

use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductFulfillmentType;
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
                'activeOrderData' => $this->activeOrderData($seller),
                'orders' => $this->latestOrders($seller),
                'topProducts' => $this->topProducts($seller),
                'stockAlerts' => $this->stockAlerts($seller),
                'tasks' => $this->tasks($seller),
            ],
        ]);
    }

    /**
     * @return array<int, array{label: string, value: string, context: string, tone: string, icon: string}>
     */
    private function stats(User $seller): array
    {
        $now = now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();

        $revenue = fn ($start, $end): int => (int) OrderItem::query()
            ->whereHas('product', fn ($q) => $q->where('seller_id', $seller->id))
            ->where('payment_status', PaymentStatus::Paid)
            ->whereBetween('created_at', [$start, $end])
            ->sum('subtotal');
        $offlineRevenue = fn ($start, $end): int => (int) UpJurusanStockMovement::query()
            ->where('type', 'out')
            ->whereHas('consignment', fn ($q) => $q->where('seller_id', $seller->id))
            ->whereBetween('created_at', [$start, $end])
            ->sum('seller_amount');

        $currentMonthRevenue = $revenue($currentMonthStart, $currentMonthEnd) + $offlineRevenue($currentMonthStart, $currentMonthEnd);
        $paidTransactions = OrderItem::query()
            ->whereHas('product', fn ($q) => $q->where('seller_id', $seller->id))
            ->where('payment_status', PaymentStatus::Paid)
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->distinct()
            ->count('order_id');
        $paidTransactions += UpJurusanStockMovement::query()
            ->where('type', 'out')
            ->whereHas('consignment', fn ($q) => $q->where('seller_id', $seller->id))
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->distinct()
            ->count('up_jurusan_pos_sale_id');

        $stockQuery = Product::query()
            ->where('seller_id', $seller->id)
            ->where('fulfillment_type', ProductFulfillmentType::ReadyStock);
        $outOfStockProducts = (clone $stockQuery)
            ->whereRaw(Product::REAL_STOCK_SQL.' = 0')
            ->count();
        $lowStockProducts = (clone $stockQuery)
            ->whereRaw(Product::REAL_STOCK_SQL.' > 0')
            ->whereRaw(Product::REAL_STOCK_SQL.' <= ?', [Product::LOW_STOCK_THRESHOLD])
            ->count();

        return [
            [
                'label' => 'Pendapatan Seller Bulan Ini',
                'value' => 'Rp '.number_format((float) $currentMonthRevenue, 0, ',', '.'),
                'context' => $currentMonthRevenue > 0
                    ? 'Online terbayar dan hak seller dari POS'
                    : 'Belum ada pendapatan seller bulan ini',
                'tone' => 'blue',
                'icon' => 'badgeDollarSign',
            ],
            [
                'label' => 'Transaksi Terbayar Bulan Ini',
                'value' => (string) $paidTransactions,
                'context' => $paidTransactions > 0 ? 'Online terbayar dan nota POS' : 'Belum ada transaksi terbayar',
                'tone' => 'emerald',
                'icon' => 'shoppingCart',
            ],
            [
                'label' => 'Stok Habis',
                'value' => (string) $outOfStockProducts,
                'context' => $outOfStockProducts > 0 ? 'Perlu segera diisi' : 'Tidak ada stok habis',
                'tone' => $outOfStockProducts > 0 ? 'rose' : 'emerald',
                'icon' => 'boxes',
            ],
            [
                'label' => 'Stok Menipis',
                'value' => (string) $lowStockProducts,
                'context' => $lowStockProducts > 0 ? 'Perlu segera diisi' : 'Stok produk aman',
                'tone' => $lowStockProducts > 0 ? 'amber' : 'emerald',
                'icon' => 'boxes',
            ],
        ];
    }

    /**
     * @return array<int, array{day: string, sales: int}>
     */
    private function salesData(User $seller): array
    {
        $start = now()->subDays(6)->startOfDay();
        $end = now()->endOfDay();

        /** @var Collection<string, object{total_sales: int}> $totals */
        $totals = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.seller_id', $seller->id)
            ->where('order_items.payment_status', PaymentStatus::Paid->value)
            ->whereBetween('order_items.created_at', [$start, $end])
            ->selectRaw('DATE(order_items.created_at) as sale_date, COALESCE(SUM(order_items.subtotal), 0) as total_sales')
            ->groupByRaw('DATE(order_items.created_at)')
            ->get()
            ->keyBy('sale_date');
        /** @var Collection<string, object{total_sales: int}> $offlineTotals */
        $offlineTotals = DB::table('up_jurusan_stock_movements')
            ->join('up_jurusan_consignments', 'up_jurusan_stock_movements.up_jurusan_consignment_id', '=', 'up_jurusan_consignments.id')
            ->where('up_jurusan_consignments.seller_id', $seller->id)
            ->where('up_jurusan_stock_movements.type', 'out')
            ->whereBetween('up_jurusan_stock_movements.created_at', [$start, $end])
            ->selectRaw('DATE(up_jurusan_stock_movements.created_at) as sale_date, COALESCE(SUM(up_jurusan_stock_movements.seller_amount), 0) as total_sales')
            ->groupByRaw('DATE(up_jurusan_stock_movements.created_at)')
            ->get()
            ->keyBy('sale_date');

        return collect(range(6, 0))
            ->map(function (int $daysAgo) use ($totals, $offlineTotals) {
                $date = now()->subDays($daysAgo);
                $dayStats = $totals->get($date->toDateString());
                $offlineDayStats = $offlineTotals->get($date->toDateString());

                return [
                    'day' => $date->translatedFormat('d M'),
                    'sales' => (int) ($dayStats->total_sales ?? 0) + (int) ($offlineDayStats->total_sales ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{key: string, label: string, value: int}>
     */
    private function activeOrderData(User $seller): array
    {
        $counts = OrderItem::query()
            ->whereHas('product', fn ($q) => $q->where('seller_id', $seller->id))
            ->whereIn('status', [
                OrderItemStatus::Pending,
                OrderItemStatus::Packed,
                OrderItemStatus::InProduction,
                OrderItemStatus::Ready,
                OrderItemStatus::Sent,
            ])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            [
                'key' => 'needs_action',
                'label' => 'Perlu Diproses',
                'value' => (int) ($counts[OrderItemStatus::Pending->value] ?? 0),
            ],
            [
                'key' => 'in_production',
                'label' => 'Sedang Diproduksi',
                'value' => (int) ($counts[OrderItemStatus::InProduction->value] ?? 0),
            ],
            [
                'key' => 'ready_to_ship',
                'label' => 'Siap Dikirim',
                'value' => (int) ($counts[OrderItemStatus::Packed->value] ?? 0)
                    + (int) ($counts[OrderItemStatus::Ready->value] ?? 0),
            ],
            [
                'key' => 'sent',
                'label' => 'Dikirim',
                'value' => (int) ($counts[OrderItemStatus::Sent->value] ?? 0),
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
                'status' => OrderItemStatus::Completed->value,
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
            ->where('order_items.payment_status', PaymentStatus::Paid->value)
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
            ->where('fulfillment_type', ProductFulfillmentType::ReadyStock)
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
        $pendingProducts = Product::query()
            ->where('seller_id', $seller->id)
            ->where('status', ProductStatus::Pending)
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
        }

        if ($pendingOrders > 0) {
            $tasks[] = [
                'title' => "{$pendingOrders} pesanan perlu diproses",
                'detail' => 'Segera proses pesanan yang masuk.',
                'action' => 'Proses pesanan',
                'icon' => 'shoppingBag',
                'tone' => 'blue',
            ];
        }

        if ($pendingProducts > 0) {
            $tasks[] = [
                'title' => "{$pendingProducts} produk menunggu moderasi",
                'detail' => 'Pantau status produk sebelum dapat dijual.',
                'action' => 'Lihat produk',
                'icon' => 'clock3',
                'tone' => 'amber',
            ];
        }

        return $tasks;
    }
}
