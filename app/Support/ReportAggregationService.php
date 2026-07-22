<?php

namespace App\Support;

use App\Enums\StockMovementSource;
use App\Models\UpJurusanDailyReport;
use App\Models\UpJurusanDailyReportTransaction;
use App\Models\UpJurusanStockMovement;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportAggregationService
{
    /**
     * Sales movements for a UP (consignment + UP-owned products).
     * Only type=out with sales sources; reverse/correction excluded.
     *
     * @param  list<string>|null  $sources
     */
    public static function upSalesMovementsQuery(
        int $upJurusanId,
        ?string $date = null,
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null,
        ?array $sources = null,
        ?int $actorUserId = null,
    ): Builder {
        $sources ??= StockMovementSource::salesSources();

        return UpJurusanStockMovement::query()
            ->where('type', 'out')
            ->whereIn('source', $sources)
            ->where(function (Builder $query) use ($upJurusanId) {
                $query->whereHas('consignment', fn (Builder $q) => $q->where('up_jurusan_id', $upJurusanId))
                    ->orWhereHas('product', fn (Builder $q) => $q->where('up_jurusan_id', $upJurusanId));
            })
            ->when($date !== null, fn (Builder $q) => $q->whereDate('created_at', $date))
            ->when($from !== null, fn (Builder $q) => $q->where('created_at', '>=', $from))
            ->when($to !== null, fn (Builder $q) => $q->where('created_at', '<=', $to))
            ->when($actorUserId !== null, fn (Builder $q) => $q->where('user_id', $actorUserId));
    }

    /**
     * @return array{total_sold: int, total_revenue: int, commission_amount: int, seller_amount: int}
     */
    public static function summarizeMovements(iterable $movements): array
    {
        $collection = Collection::make($movements);

        return [
            'total_sold' => (int) $collection->sum('quantity'),
            'total_revenue' => (int) $collection->sum('gross_amount'),
            'commission_amount' => (int) $collection->sum('commission_amount'),
            'seller_amount' => (int) $collection->sum('seller_amount'),
        ];
    }

    /**
     * UP revenue for a day: UP-owned gross + consignment commission (net UP share).
     */
    public static function upRevenueFromMovements(iterable $movements): int
    {
        return (int) Collection::make($movements)->sum(function (UpJurusanStockMovement $movement) {
            if ($movement->up_jurusan_consignment_id === null) {
                return (int) $movement->gross_amount;
            }

            return (int) $movement->commission_amount;
        });
    }

    /**
     * Open picket daily report (live) from stock movements for that picket + day.
     * Includes POS and online sales attributed to the UP; POS lines filtered by picket actor.
     *
     * @return array{total_sold: int, total_revenue: int, submitted_at: null, items: array<int, array<string, mixed>>}
     */
    public static function picketDailyOpenSummary(int $upJurusanId, int $picketUserId, string $date): array
    {
        $posMovements = self::upSalesMovementsQuery(
            $upJurusanId,
            date: $date,
            sources: [StockMovementSource::PosSale->value],
            actorUserId: $picketUserId,
        )
            ->with(['consignment.product:id,name', 'product:id,name', 'posSale'])
            ->orderByDesc('id')
            ->get();

        $onlineMovements = self::upSalesMovementsQuery(
            $upJurusanId,
            date: $date,
            sources: [StockMovementSource::OnlineOrder->value],
        )
            ->with(['consignment.product:id,name', 'product:id,name', 'order:id,code'])
            ->orderByDesc('id')
            ->get();

        $items = self::groupPosTransactions($posMovements)
            ->concat(self::groupOnlineTransactions($onlineMovements))
            ->sortByDesc(fn (array $row) => $row['sold_at'] ?? '')
            ->values()
            ->all();

        $all = $posMovements->concat($onlineMovements);
        $summary = self::summarizeMovements($all);

        return [
            'total_sold' => $summary['total_sold'],
            'total_revenue' => $summary['total_revenue'],
            'submitted_at' => null,
            'items' => $items,
        ];
    }

    /**
     * Snapshot totals for submitting a picket daily report (POS + online for UP that day).
     *
     * @return array{total_sold: int, total_revenue: int, items: array<int, array<string, mixed>>}
     */
    public static function picketDailySubmitPayload(int $upJurusanId, int $picketUserId, string $date): array
    {
        $open = self::picketDailyOpenSummary($upJurusanId, $picketUserId, $date);

        return [
            'total_sold' => $open['total_sold'],
            'total_revenue' => $open['total_revenue'],
            'items' => $open['items'],
        ];
    }

    /**
     * @return array{total_sold: int, total_revenue: int, items: array<int, array<string, mixed>>}
     */
    public static function dailyReportSnapshotPayload(UpJurusanDailyReport $report): array
    {
        $report->loadMissing(['transactions.items']);

        return [
            'total_sold' => (int) $report->total_sold,
            'total_revenue' => (int) $report->total_revenue,
            'items' => $report->transactions
                ->sortByDesc('sold_at')
                ->map(fn (UpJurusanDailyReportTransaction $transaction) => [
                    'id' => $transaction->id,
                    'code' => $transaction->code,
                    'receipt_url' => $transaction->up_jurusan_pos_sale_id === null
                        ? '#'
                        : route('picket.pos.receipt', $transaction->up_jurusan_pos_sale_id, absolute: false),
                    'sold_at' => $transaction->sold_at?->toIso8601String(),
                    'total_quantity' => $transaction->total_quantity,
                    'total_amount' => $transaction->total_amount,
                    'commission_amount' => $transaction->commission_amount,
                    'seller_amount' => $transaction->seller_amount,
                    'channel' => $transaction->up_jurusan_pos_sale_id === null
                        ? StockMovementSource::OnlineOrder->value
                        : StockMovementSource::PosSale->value,
                    'products' => $transaction->items
                        ->map(fn ($item) => [
                            'product_name' => $item->product_name,
                            'source' => $item->source,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'subtotal' => $item->subtotal,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Admin UP revenue chart (last N days) from stock movements only.
     *
     * @return array<int, array{day: string, revenue: int}>
     */
    public static function upRevenueChart(int $upJurusanId, int $days = 7): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $movements = self::upSalesMovementsQuery($upJurusanId, from: $start)
            ->get(['created_at', 'gross_amount', 'commission_amount', 'up_jurusan_consignment_id']);

        $byDate = $movements->groupBy(fn (UpJurusanStockMovement $m) => $m->created_at?->toDateString() ?? '');

        return collect(range($days - 1, 0))
            ->map(function (int $daysAgo) use ($byDate) {
                $date = now()->subDays($daysAgo);
                $key = $date->toDateString();

                return [
                    'day' => $date->translatedFormat('D'),
                    'revenue' => self::upRevenueFromMovements($byDate->get($key, collect())),
                ];
            })
            ->values()
            ->all();
    }

    public static function upRevenueTotal(int $upJurusanId, CarbonInterface $from, ?CarbonInterface $to = null): int
    {
        $movements = self::upSalesMovementsQuery($upJurusanId, from: $from, to: $to)
            ->get(['gross_amount', 'commission_amount', 'up_jurusan_consignment_id']);

        return self::upRevenueFromMovements($movements);
    }

    public static function upTodaySales(int $upJurusanId, ?string $date = null): int
    {
        $date ??= now()->toDateString();
        $movements = self::upSalesMovementsQuery($upJurusanId, date: $date)
            ->get(['gross_amount', 'commission_amount', 'up_jurusan_consignment_id']);

        // Dashboard "today sales" historically used POS total_amount (gross). Keep gross total for all channels.
        return (int) $movements->sum('gross_amount');
    }

    /**
     * Seller offline (POS) revenue from stored seller_amount.
     * Online consignment is already counted via paid OrderItem — do not include online_order here.
     */
    public static function sellerOfflineRevenue(int $sellerId, CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) self::sellerPosSalesMovementsQuery($sellerId, $from, $to)->sum('seller_amount');
    }

    /**
     * @return Collection<string, int> date => seller_amount
     */
    public static function sellerOfflineRevenueByDate(int $sellerId, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return self::sellerPosSalesMovementsQuery($sellerId, $from, $to)
            ->get(['created_at', 'seller_amount'])
            ->groupBy(fn (UpJurusanStockMovement $m) => $m->created_at?->toDateString() ?? '')
            ->map(fn (Collection $rows) => (int) $rows->sum('seller_amount'));
    }

    /**
     * Count POS receipts (distinct pos sale ids) for seller dashboard transaction tally.
     * Movements without a pos sale id each count as one transaction.
     */
    public static function sellerOfflineSaleCount(int $sellerId, CarbonInterface $from, CarbonInterface $to): int
    {
        $movements = self::sellerPosSalesMovementsQuery($sellerId, $from, $to)
            ->get(['up_jurusan_pos_sale_id']);

        $withSaleId = $movements
            ->whereNotNull('up_jurusan_pos_sale_id')
            ->pluck('up_jurusan_pos_sale_id')
            ->unique()
            ->count();
        $withoutSaleId = $movements->whereNull('up_jurusan_pos_sale_id')->count();

        return $withSaleId + $withoutSaleId;
    }

    private static function sellerPosSalesMovementsQuery(
        int $sellerId,
        CarbonInterface $from,
        CarbonInterface $to,
    ): Builder {
        return UpJurusanStockMovement::query()
            ->where('type', 'out')
            ->where('source', StockMovementSource::PosSale)
            ->whereHas('consignment', fn (Builder $q) => $q->where('seller_id', $sellerId))
            ->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Admin jurusan submitted daily reports summary for a date.
     *
     * @param  Collection<int, UpJurusanDailyReport>  $reports
     * @return array{reports: int, pickets: int, items_sold: int, gross_amount: int}
     */
    public static function adminDailyReportsSummary(Collection $reports): array
    {
        return [
            'reports' => $reports->count(),
            'pickets' => $reports->pluck('user_id')->unique()->count(),
            'items_sold' => (int) $reports->sum('total_sold'),
            'gross_amount' => (int) $reports->sum('total_revenue'),
        ];
    }

    /**
     * @param  Collection<int, UpJurusanStockMovement>  $movements
     * @return Collection<int, array<string, mixed>>
     */
    private static function groupPosTransactions(Collection $movements): Collection
    {
        return $movements
            ->groupBy(fn (UpJurusanStockMovement $m) => $m->up_jurusan_pos_sale_id ?? 'm-'.$m->id)
            ->map(function (Collection $group) {
                /** @var UpJurusanStockMovement $first */
                $first = $group->first();
                $sale = $first->posSale;
                $summary = self::summarizeMovements($group);

                return [
                    'id' => $sale?->id ?? $first->id,
                    'code' => $sale?->code ?? 'POS-'.$first->id,
                    'receipt_url' => $sale
                        ? route('picket.pos.receipt', $sale, absolute: false)
                        : '#',
                    'sold_at' => ($sale?->created_at ?? $first->created_at)?->toIso8601String(),
                    'total_quantity' => $summary['total_sold'],
                    'total_amount' => $summary['total_revenue'],
                    'commission_amount' => $summary['commission_amount'],
                    'seller_amount' => $summary['seller_amount'],
                    'channel' => StockMovementSource::PosSale->value,
                    'products' => self::productLines($group),
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, UpJurusanStockMovement>  $movements
     * @return Collection<int, array<string, mixed>>
     */
    private static function groupOnlineTransactions(Collection $movements): Collection
    {
        return $movements
            ->groupBy(fn (UpJurusanStockMovement $m) => $m->order_id ?? 'm-'.$m->id)
            ->map(function (Collection $group) {
                /** @var UpJurusanStockMovement $first */
                $first = $group->first();
                $order = $first->order;
                $summary = self::summarizeMovements($group);

                return [
                    'id' => $order?->id ?? $first->id,
                    'code' => $order?->code ?? 'ORD-'.$first->id,
                    'receipt_url' => '#',
                    'sold_at' => $first->created_at?->toIso8601String(),
                    'total_quantity' => $summary['total_sold'],
                    'total_amount' => $summary['total_revenue'],
                    'commission_amount' => $summary['commission_amount'],
                    'seller_amount' => $summary['seller_amount'],
                    'channel' => StockMovementSource::OnlineOrder->value,
                    'products' => self::productLines($group),
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, UpJurusanStockMovement>  $group
     * @return array<int, array{product_name: string, source: string, quantity: int, unit_price: int, subtotal: int}>
     */
    private static function productLines(Collection $group): array
    {
        return $group
            ->map(function (UpJurusanStockMovement $movement) {
                $product = $movement->up_jurusan_consignment_id === null
                    ? $movement->product
                    : $movement->consignment?->product;

                return [
                    'product_name' => $product?->name ?? '-',
                    'source' => $movement->up_jurusan_consignment_id === null ? 'Produk UP' : 'Titipan Seller',
                    'quantity' => $movement->quantity,
                    'unit_price' => $movement->unit_price,
                    'subtotal' => $movement->gross_amount,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Persist daily report snapshot rows from open payload items.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public static function snapshotDailyReportTransactions(UpJurusanDailyReport $report, array $items): void
    {
        foreach ($items as $item) {
            $posSaleId = ($item['channel'] ?? null) === StockMovementSource::PosSale->value
                ? ($item['id'] ?? null)
                : null;

            $transaction = $report->transactions()->create([
                'up_jurusan_pos_sale_id' => $posSaleId,
                'code' => $item['code'],
                'total_quantity' => $item['total_quantity'],
                'total_amount' => $item['total_amount'],
                'commission_amount' => $item['commission_amount'],
                'seller_amount' => $item['seller_amount'],
                'sold_at' => $item['sold_at'] ?? now(),
            ]);

            foreach ($item['products'] as $product) {
                $transaction->items()->create([
                    'up_jurusan_stock_movement_id' => null,
                    'product_name' => $product['product_name'],
                    'source' => $product['source'],
                    'quantity' => $product['quantity'],
                    'unit_price' => $product['unit_price'],
                    'subtotal' => $product['subtotal'],
                ]);
            }
        }
    }
}
