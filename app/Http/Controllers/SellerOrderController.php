<?php

namespace App\Http\Controllers;

use App\Enums\OrderItemStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Requests\Seller\UpdateOrderItemStatusRequest;
use App\Models\OrderItem;
use App\Models\UpJurusanStockMovement;
use App\Models\User;
use App\Support\OrderPaymentSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SellerOrderController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $seller */
        $seller = $request->user();

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(OrderItemStatus::class)],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = OrderItem::query()
            ->with([
                'order:id,code,user_id,created_at',
                'order.user:id,name',
                'product:id,name,slug,seller_id,sales_method',
            ])
            ->whereHas('product', fn ($q) => $q->where('seller_id', $seller->id));

        if ($search = $validated['q'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhereHas('order.user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                    ->when(is_numeric($search), fn ($query) => $query->orWhere('order_id', (int) $search))
                    ->when(str_contains($search, '-'), fn ($query) => $query->orWhereHas('order', fn ($oq) => $oq->where('code', 'like', "%{$search}%")));
            });
        }

        if ($status = $validated['status'] ?? null) {
            $query->where('status', $status);
        }

        $onlineItems = $query
            ->latest('order_items.created_at')
            ->get()
            ->map(fn (OrderItem $item): array => $this->onlineOrderPayload($item));

        $offlineItems = UpJurusanStockMovement::query()
            ->with([
                'user:id,name',
                'posSale:id,code',
                'consignment.product:id,name,slug,seller_id',
            ])
            ->where('type', 'out')
            ->whereHas('consignment', fn ($q) => $q->where('seller_id', $seller->id))
            ->when($search ?? null, function ($q, string $search) {
                $q->where(function ($q) use ($search) {
                    $q->whereHas('consignment.product', fn ($pq) => $pq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('posSale', fn ($sq) => $sq->where('code', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->get()
            ->map(fn (UpJurusanStockMovement $movement): array => $this->offlineOrderPayload($movement));

        if (($validated['status'] ?? null) === null) {
            $items = collect($onlineItems->all())
                ->merge($offlineItems)
                ->sortByDesc('created_at')
                ->values();
        } else {
            $items = $onlineItems;
        }

        $perPage = 10;
        $page = Paginator::resolveCurrentPage();
        $orderItems = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'query' => $request->query(),
            ],
        );

        return Inertia::render('seller/orders/index', [
            'orderItems' => $orderItems,
            'filters' => [
                'q' => $validated['q'] ?? '',
                'status' => $validated['status'] ?? '',
            ],
        ]);
    }

    public function show(Request $request, OrderItem $orderItem): Response
    {
        /** @var User $seller */
        $seller = $request->user();

        if ($orderItem->product->seller_id !== $seller->id) {
            abort(403);
        }

        $orderItem->load([
            'order:id,code,user_id,created_at',
            'order.user:id,name',
            'product:id,name,slug,seller_id,sales_method,category_id',
            'product.category:id,name,slug',
        ]);

        /** @var OrderItem $orderItem */

        return Inertia::render('seller/orders/show', [
            'orderItem' => [
                'id' => $orderItem->id,
                'code' => $orderItem->order->code ?? "TRX-{$orderItem->order_id}",
                'order_id' => $orderItem->order_id,
                'buyer' => [
                    'id' => $orderItem->order->user->id,
                    'name' => $orderItem->order->user->name,
                ],
                'product' => [
                    'id' => $orderItem->product->id,
                    'name' => $orderItem->product->name,
                    'slug' => $orderItem->product->slug,
                    'category' => [
                        'id' => $orderItem->product->category->id,
                        'name' => $orderItem->product->category->name,
                        'slug' => $orderItem->product->category->slug,
                    ],
                ],
                'managed_by_up_jurusan' => $orderItem->product->usesConsignmentStock(),
                'is_pre_order' => $orderItem->is_pre_order,
                'pre_order_estimate_days' => $orderItem->pre_order_estimate_days,
                'pre_order_deadline' => $orderItem->pre_order_deadline?->toDateString(),
                'pre_order_min_quantity' => $orderItem->pre_order_min_quantity,
                'pre_order_note' => $orderItem->pre_order_note,
                'product_name' => $orderItem->product_name,
                'price' => $orderItem->price,
                'quantity' => $orderItem->quantity,
                'subtotal' => $orderItem->subtotal,
                'status' => [
                    'code' => $orderItem->status->value,
                    'label' => $orderItem->status->label(),
                ],
                'payment' => $this->paymentPayload($orderItem),
                'created_at' => $orderItem->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function showOffline(Request $request, UpJurusanStockMovement $movement): Response
    {
        /** @var User $seller */
        $seller = $request->user();

        $movement->load([
            'user:id,name',
            'posSale:id,code,created_at',
            'consignment.upJurusan:id,name',
            'consignment.product:id,name,slug,seller_id,category_id',
            'consignment.product.category:id,name,slug',
        ]);

        abort_unless($movement->type === 'out' && $movement->consignment->seller_id === $seller->id, 403);

        return Inertia::render('seller/orders/show', [
            'orderItem' => $this->offlineOrderDetailPayload($movement),
        ]);
    }

    public function updateStatus(UpdateOrderItemStatusRequest $request, OrderItem $orderItem): RedirectResponse
    {
        /** @var User $seller */
        $seller = $request->user();

        if ($orderItem->product->seller_id !== $seller->id) {
            abort(403);
        }

        if ($orderItem->product->usesConsignmentStock()) {
            throw ValidationException::withMessages([
                'status' => 'Status pengiriman produk titipan dikelola oleh picket officer UP Jurusan.',
            ]);
        }

        $newStatus = OrderItemStatus::from($request->string('status')->toString());

        if ($newStatus === OrderItemStatus::Completed) {
            throw ValidationException::withMessages([
                'status' => 'Pesanan selesai hanya bisa dikonfirmasi oleh buyer setelah diterima.',
            ]);
        }

        DB::transaction(function () use ($orderItem, $newStatus) {
            /** @var OrderItem $current */
            $current = OrderItem::query()
                ->lockForUpdate()
                ->findOrFail($orderItem->id);

            $expectedNext = $current->is_pre_order
                ? $current->status->nextForPreOrder()
                : $current->status->next();

            if ($expectedNext === null || $newStatus !== $expectedNext) {
                throw ValidationException::withMessages([
                    'status' => 'Status tidak valid. Seller hanya dapat mengubah status sampai dikirim.',
                ]);
            }

            $current->update(['status' => $newStatus]);
        });

        return to_route('seller.orders.index')
            ->with('success', 'Status pesanan berhasil diperbarui.');
    }

    public function approvePayment(Request $request, OrderItem $orderItem): RedirectResponse
    {
        /** @var User $seller */
        $seller = $request->user();

        DB::transaction(function () use ($orderItem, $seller) {
            /** @var OrderItem $current */
            $current = OrderItem::query()
                ->with(['order:id', 'product:id,seller_id,up_jurusan_id,sales_method'])
                ->lockForUpdate()
                ->findOrFail($orderItem->id);

            if ($current->product->seller_id !== $seller->id) {
                abort(403);
            }

            if ($current->product->usesConsignmentStock()) {
                throw ValidationException::withMessages([
                    'payment' => 'Pelunasan produk titipan dikonfirmasi oleh picket officer UP Jurusan.',
                ]);
            }

            if ($current->payment_status === PaymentStatus::Paid) {
                throw ValidationException::withMessages([
                    'payment' => 'Pembayaran item ini sudah lunas.',
                ]);
            }

            $current->update([
                'payment_status' => PaymentStatus::Paid,
                'payment_method' => PaymentMethod::Cash,
                'payment_confirmed_at' => now(),
                'payment_confirmed_by' => $seller->id,
                'payment_rejection_reason' => null,
            ]);

            OrderPaymentSync::sync($current->order);
        });

        return back()->with('success', 'Pelunasan item berhasil dikonfirmasi.');
    }

    /**
     * @return array<string, mixed>
     */
    private function onlineOrderPayload(OrderItem $item): array
    {
        return [
            'id' => $item->id,
            'source' => 'online',
            'code' => $item->order->code ?? "TRX-{$item->order_id}",
            'order_id' => $item->order_id,
            'buyer' => [
                'id' => $item->order->user->id,
                'name' => $item->order->user->name,
            ],
            'product' => [
                'id' => $item->product->id,
                'name' => $item->product->name,
                'slug' => $item->product->slug,
            ],
            'managed_by_up_jurusan' => $item->product->usesConsignmentStock(),
            'is_pre_order' => $item->is_pre_order,
            'pre_order_estimate_days' => $item->pre_order_estimate_days,
            'pre_order_deadline' => $item->pre_order_deadline?->toDateString(),
            'pre_order_min_quantity' => $item->pre_order_min_quantity,
            'pre_order_note' => $item->pre_order_note,
            'product_name' => $item->product_name,
            'price' => $item->price,
            'quantity' => $item->quantity,
            'subtotal' => $item->subtotal,
            'status' => [
                'code' => $item->status->value,
                'label' => $item->status->label(),
            ],
            'payment' => $this->paymentPayload($item),
            'created_at' => $item->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function offlineOrderPayload(UpJurusanStockMovement $movement): array
    {
        return [
            'id' => $movement->id,
            'source' => 'offline',
            'detail_url' => route('seller.orders.offline.show', $movement, absolute: false),
            'code' => $movement->posSale->code ?? "TRX-OFF-{$movement->id}",
            'order_id' => $movement->posSale->code ?? "OFF-{$movement->id}",
            'buyer' => [
                'id' => null,
                'name' => 'Pembeli offline',
            ],
            'product' => [
                'id' => $movement->consignment->product->id,
                'name' => $movement->consignment->product->name,
                'slug' => $movement->consignment->product->slug,
            ],
            'managed_by_up_jurusan' => true,
            'is_pre_order' => false,
            'pre_order_estimate_days' => null,
            'pre_order_deadline' => null,
            'pre_order_min_quantity' => null,
            'pre_order_note' => null,
            'product_name' => $movement->consignment->product->name,
            'price' => $movement->unit_price,
            'quantity' => $movement->quantity,
            'subtotal' => $movement->seller_amount,
            'status' => [
                'code' => OrderItemStatus::Sent->value,
                'label' => 'Terjual offline',
            ],
            'payment' => [
                'status' => [
                    'code' => PaymentStatus::Paid->value,
                    'label' => PaymentStatus::Paid->label(),
                ],
                'method' => [
                    'code' => PaymentMethod::Cash->value,
                    'label' => PaymentMethod::Cash->label(),
                ],
                'confirmed_at' => $movement->created_at?->toIso8601String(),
                'rejection_reason' => null,
            ],
            'created_at' => $movement->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function offlineOrderDetailPayload(UpJurusanStockMovement $movement): array
    {
        return [
            'id' => $movement->id,
            'source' => 'offline',
            'code' => $movement->posSale->code ?? "TRX-OFF-{$movement->id}",
            'order_id' => $movement->posSale->code ?? "OFF-{$movement->id}",
            'buyer' => [
                'id' => null,
                'name' => 'Pembeli offline',
            ],
            'picket' => [
                'id' => $movement->user->id,
                'name' => $movement->user->name,
            ],
            'up_jurusan' => [
                'id' => $movement->consignment->upJurusan->id,
                'name' => $movement->consignment->upJurusan->name,
            ],
            'product' => [
                'id' => $movement->consignment->product->id,
                'name' => $movement->consignment->product->name,
                'slug' => $movement->consignment->product->slug,
                'category' => [
                    'id' => $movement->consignment->product->category->id,
                    'name' => $movement->consignment->product->category->name,
                    'slug' => $movement->consignment->product->category->slug,
                ],
            ],
            'managed_by_up_jurusan' => true,
            'is_pre_order' => false,
            'pre_order_estimate_days' => null,
            'pre_order_deadline' => null,
            'pre_order_min_quantity' => null,
            'pre_order_note' => null,
            'product_name' => $movement->consignment->product->name,
            'price' => $movement->unit_price,
            'quantity' => $movement->quantity,
            'gross_amount' => $movement->gross_amount,
            'commission_amount' => $movement->commission_amount,
            'seller_amount' => $movement->seller_amount,
            'subtotal' => $movement->seller_amount,
            'status' => [
                'code' => OrderItemStatus::Sent->value,
                'label' => 'Terjual offline',
            ],
            'payment' => [
                'status' => [
                    'code' => PaymentStatus::Paid->value,
                    'label' => PaymentStatus::Paid->label(),
                ],
                'method' => [
                    'code' => PaymentMethod::Cash->value,
                    'label' => PaymentMethod::Cash->label(),
                ],
                'confirmed_at' => $movement->created_at?->toIso8601String(),
                'rejection_reason' => null,
            ],
            'created_at' => $movement->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{status: array{code: string, label: string}, method: array{code: string, label: string}, confirmed_at: string|null, rejection_reason: string|null}
     */
    private function paymentPayload(OrderItem $item): array
    {
        return [
            'status' => [
                'code' => $item->payment_status->value,
                'label' => $item->payment_status->label(),
            ],
            'method' => [
                'code' => $item->payment_method->value,
                'label' => $item->payment_method->label(),
            ],
            'confirmed_at' => $item->payment_confirmed_at?->toIso8601String(),
            'rejection_reason' => $item->payment_rejection_reason,
        ];
    }
}
