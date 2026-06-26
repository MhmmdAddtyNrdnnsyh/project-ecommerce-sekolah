<?php

namespace App\Http\Controllers;

use App\Enums\OrderItemStatus;
use App\Enums\UpJurusanConsignmentStatus;
use App\Models\OrderItem;
use App\Models\UpJurusanConsignment;
use App\Models\UpJurusanStockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PicketUpJurusanConsignmentController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->pos($request);
    }

    public function dashboard(Request $request): Response
    {
        return Inertia::render('picket/dashboard', $this->pageData($request));
    }

    public function pos(Request $request): Response
    {
        return Inertia::render('picket/up-jurusan/consignments/index', $this->pageData($request));
    }

    public function orders(Request $request): Response
    {
        return Inertia::render('picket/orders', $this->pageData($request));
    }

    public function reports(Request $request): Response
    {
        return Inertia::render('picket/reports', $this->pageData($request));
    }

    /**
     * @return array{
     *     up_jurusan: array{id: int, name: string}|null,
     *     pos_products: array<int, array{id: int, seller_name: string, product_name: string, price: int, available_quantity: int}>,
     *     daily_report: array{date: string, total_sold: int, total_revenue: int, items: array<int, array{product_name: string, quantity: int, subtotal: int}>},
     *     consignments: array<int, array{id: int, seller_name: string, product_name: string, up_jurusan_name: string, requested_quantity: int, received_quantity: int, sold_quantity: int, status: array{code: string, label: string}}>,
     *     order_items: array<int, array{id: int, order_id: int, buyer_name: string, seller_name: string, product_name: string, quantity: int, subtotal: int, status: array{code: string, label: string}, created_at: string|null}>
     * }
     */
    private function pageData(Request $request): array
    {
        /** @var User $picket */
        $picket = $request->user();
        $picket->load('upJurusan:id,name');
        $today = now()->toDateString();
        $consignments = UpJurusanConsignment::query()
            ->with(['seller:id,name', 'product:id,name,price', 'upJurusan:id,name'])
            ->where('up_jurusan_id', $picket->up_jurusan_id)
            ->latest()
            ->get();
        $dailyMovements = UpJurusanStockMovement::query()
            ->with('consignment.product:id,name,price')
            ->where('user_id', $picket->id)
            ->where('type', 'out')
            ->whereDate('created_at', $today)
            ->whereHas('consignment', fn ($query) => $query->where('up_jurusan_id', $picket->up_jurusan_id))
            ->get();
        $orderItems = OrderItem::query()
            ->with([
                'order:id,user_id,created_at',
                'order.user:id,name',
                'product:id,name,seller_id',
                'product.seller:id,name',
            ])
            ->whereHas('product.upJurusanConsignments', fn ($query) => $query->where('up_jurusan_id', $picket->up_jurusan_id))
            ->latest()
            ->limit(30)
            ->get();

        return [
            'up_jurusan' => $picket->upJurusan ? [
                'id' => $picket->upJurusan->id,
                'name' => $picket->upJurusan->name,
            ] : null,
            'pos_products' => $consignments
                ->filter(fn (UpJurusanConsignment $consignment) => $consignment->received_quantity > $consignment->sold_quantity)
                ->map(fn (UpJurusanConsignment $consignment) => [
                    'id' => $consignment->id,
                    'seller_name' => $consignment->seller->name,
                    'product_name' => $consignment->product->name,
                    'price' => $consignment->product->price,
                    'available_quantity' => $consignment->received_quantity - $consignment->sold_quantity,
                ])
                ->values()
                ->all(),
            'daily_report' => [
                'date' => $today,
                'total_sold' => $dailyMovements->sum('quantity'),
                'total_revenue' => $dailyMovements->sum(fn (UpJurusanStockMovement $movement): int => $movement->quantity * $movement->consignment->product->price),
                'items' => $this->dailyReportItems($dailyMovements),
            ],
            'consignments' => $consignments
                ->map(fn (UpJurusanConsignment $consignment) => [
                    'id' => $consignment->id,
                    'seller_name' => $consignment->seller->name,
                    'product_name' => $consignment->product->name,
                    'up_jurusan_name' => $consignment->upJurusan->name,
                    'requested_quantity' => $consignment->requested_quantity,
                    'received_quantity' => $consignment->received_quantity,
                    'sold_quantity' => $consignment->sold_quantity,
                    'status' => [
                        'code' => $consignment->status->value,
                        'label' => $consignment->status->label(),
                    ],
                ])
                ->all(),
            'order_items' => $orderItems
                ->map(fn (OrderItem $item) => [
                    'id' => $item->id,
                    'order_id' => $item->order_id,
                    'buyer_name' => $item->order->user->name,
                    'seller_name' => $item->product->seller->name,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal,
                    'status' => [
                        'code' => $item->status->value,
                        'label' => $item->status->label(),
                    ],
                    'created_at' => $item->created_at?->toIso8601String(),
                ])
                ->all(),
        ];
    }

    public function receive(Request $request, UpJurusanConsignment $consignment): RedirectResponse
    {
        abort(403);
    }

    public function release(Request $request, UpJurusanConsignment $consignment): RedirectResponse
    {
        /** @var User $picket */
        $picket = $request->user();
        $this->authorizePicket($picket, $consignment);

        $quantity = $this->quantity($request);

        DB::transaction(function () use ($consignment, $picket, $quantity) {
            $this->recordSale($picket, $consignment, $quantity);
        });

        return to_route('picket.pos')
            ->with('success', 'Barang keluar berhasil dicatat.');
    }

    public function storeSale(Request $request): RedirectResponse
    {
        /** @var User $picket */
        $picket = $request->user();

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($validated, $picket) {
            foreach ($validated['items'] as $item) {
                $consignment = UpJurusanConsignment::query()
                    ->with('product:id,price')
                    ->whereKey($item['id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->authorizePicket($picket, $consignment);
                $this->recordSale($picket, $consignment, (int) $item['quantity']);
            }
        });

        return to_route('picket.pos')
            ->with('success', 'Penjualan berhasil dicatat.');
    }

    public function storeReport(Request $request): RedirectResponse
    {
        /** @var User $picket */
        $picket = $request->user();

        $hasSales = UpJurusanStockMovement::query()
            ->where('user_id', $picket->id)
            ->where('type', 'out')
            ->whereDate('created_at', now()->toDateString())
            ->whereHas('consignment', fn ($query) => $query->where('up_jurusan_id', $picket->up_jurusan_id))
            ->exists();

        if (! $hasSales) {
            throw ValidationException::withMessages([
                'report' => 'Belum ada penjualan hari ini.',
            ]);
        }

        return to_route('picket.reports')
            ->with('success', 'Laporan penjualan hari ini sudah dibuat.');
    }

    public function updateOrderStatus(Request $request, OrderItem $orderItem): RedirectResponse
    {
        /** @var User $picket */
        $picket = $request->user();

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:packed,sent'],
        ]);

        DB::transaction(function () use ($orderItem, $picket, $validated) {
            /** @var OrderItem $current */
            $current = OrderItem::query()
                ->with('product.upJurusanConsignments:id,product_id,up_jurusan_id')
                ->lockForUpdate()
                ->findOrFail($orderItem->id);

            $this->authorizeOrderItemPicket($picket, $current);

            $newStatus = OrderItemStatus::from($validated['status']);
            $expectedNext = $current->status->next();

            if ($expectedNext === null || $newStatus !== $expectedNext) {
                throw ValidationException::withMessages([
                    'status' => 'Status tidak valid. Perubahan harus berurutan: pending, dikemas, lalu dikirim.',
                ]);
            }

            $current->update(['status' => $newStatus]);
        });

        return to_route('picket.orders')
            ->with('success', 'Status pesanan berhasil diperbarui.');
    }

    private function quantity(Request $request): int
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        return (int) $validated['quantity'];
    }

    private function authorizePicket(User $picket, UpJurusanConsignment $consignment): void
    {
        abort_unless($picket->up_jurusan_id !== null && $consignment->up_jurusan_id === $picket->up_jurusan_id, 403);
    }

    private function authorizeOrderItemPicket(User $picket, OrderItem $orderItem): void
    {
        abort_unless(
            $picket->up_jurusan_id !== null
            && $orderItem->product->upJurusanConsignments->contains('up_jurusan_id', $picket->up_jurusan_id),
            403,
        );
    }

    private function recordSale(User $picket, UpJurusanConsignment $consignment, int $quantity): void
    {
        $available = $consignment->received_quantity - $consignment->sold_quantity;

        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah keluar tidak boleh melebihi stok titipan tersedia.',
            ]);
        }

        $nextSoldQuantity = $consignment->sold_quantity + $quantity;
        $unitPrice = $consignment->product->price;
        $grossAmount = $unitPrice * $quantity;
        $commissionAmount = intdiv($grossAmount * $consignment->commission_rate, 100);

        $consignment->update([
            'sold_quantity' => $nextSoldQuantity,
            'status' => $nextSoldQuantity >= $consignment->received_quantity
                ? UpJurusanConsignmentStatus::Completed
                : $consignment->status,
        ]);

        UpJurusanStockMovement::query()->create([
            'up_jurusan_consignment_id' => $consignment->id,
            'user_id' => $picket->id,
            'type' => 'out',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'gross_amount' => $grossAmount,
            'commission_amount' => $commissionAmount,
            'seller_amount' => $grossAmount - $commissionAmount,
        ]);
    }

    /**
     * @param  Collection<int, UpJurusanStockMovement>  $movements
     * @return array<int, array{product_name: string, quantity: int, subtotal: int}>
     */
    private function dailyReportItems(Collection $movements): array
    {
        return $movements
            ->groupBy('up_jurusan_consignment_id')
            ->map(function (Collection $items) {
                /** @var UpJurusanStockMovement $first */
                $first = $items->first();
                $quantity = $items->sum('quantity');

                return [
                    'product_name' => $first->consignment->product->name,
                    'quantity' => $quantity,
                    'subtotal' => $quantity * $first->consignment->product->price,
                ];
            })
            ->values()
            ->all();
    }
}
