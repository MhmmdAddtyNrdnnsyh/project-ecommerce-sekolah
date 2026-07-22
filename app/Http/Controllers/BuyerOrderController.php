<?php

namespace App\Http\Controllers;

use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Support\OrderItemCancellation;
use App\Support\OrderItemFulfillment;
use App\Support\OrderSettlementService;
use App\Support\OrderStatusSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BuyerOrderController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $buyer */
        $buyer = $request->user();

        $orders = Order::query()
            ->with(['items.product.seller:id,name', 'items.product.upJurusan:id,name'])
            ->withCount('items')
            ->where('user_id', $buyer->id)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('orders/index', [
            'orders' => $orders->through(fn (Order $order) => $this->orderPayload($order, 3)),
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        /** @var User $buyer */
        $buyer = $request->user();

        abort_unless($order->user_id === $buyer->id, 404);

        $order->load(['items.product.seller:id,name', 'items.product.upJurusan:id,name'])->loadCount('items');

        return Inertia::render('orders/show', [
            'order' => $this->orderPayload($order),
        ]);
    }

    public function complete(Request $request, Order $order): RedirectResponse
    {
        /** @var User $buyer */
        $buyer = $request->user();

        abort_unless($order->user_id === $buyer->id, 404);

        DB::transaction(function () use ($order) {
            /** @var Order $current */
            $current = Order::query()
                ->with('items:id,order_id,status,payment_status')
                ->lockForUpdate()
                ->findOrFail($order->id);

            $sentItems = $current->items
                ->filter(fn (OrderItem $item) => $item->status === OrderItemStatus::Sent);

            if ($sentItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'order' => 'Tidak ada item yang sedang dikirim untuk diselesaikan.',
                ]);
            }

            foreach ($sentItems as $item) {
                OrderItemFulfillment::assertCanComplete($item);
            }

            OrderItem::query()
                ->whereIn('id', $sentItems->pluck('id'))
                ->update(['status' => OrderItemStatus::Completed]);

            OrderStatusSync::sync($current->fresh(['items']));
        });

        return to_route('orders.show', $order)
            ->with('success', 'Pesanan berhasil ditandai selesai.');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        /** @var User $buyer */
        $buyer = $request->user();

        abort_unless($order->user_id === $buyer->id, 404);

        $validated = $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        OrderItemCancellation::cancelOrder(
            $order,
            $buyer,
            $validated['cancel_reason'] ?? 'Dibatalkan oleh pembeli',
        );

        return to_route('orders.show', $order)
            ->with('success', 'Pesanan berhasil dibatalkan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(Order $order, ?int $itemLimit = null): array
    {
        $items = $itemLimit === null ? $order->items : $order->items->take($itemLimit);

        return [
            'id' => $order->id,
            'code' => $order->code ?? "TRX-{$order->id}",
            'status' => OrderSettlementService::statusPayload($order),
            'can_complete' => $order->items->contains(
                fn (OrderItem $item) => $item->status === OrderItemStatus::Sent
                    && $item->payment_status === PaymentStatus::Paid
            ),
            'can_cancel' => $order->items->contains(
                fn (OrderItem $item) => $item->status !== OrderItemStatus::Cancelled
                    && $item->status !== OrderItemStatus::Completed
                    && $item->payment_status !== PaymentStatus::Paid
            ),
            'total_price' => $order->total_price,
            'payment' => [
                'status' => [
                    'code' => $order->payment_status->value,
                    'label' => $order->payment_status->label(),
                ],
                'method' => [
                    'code' => $order->payment_method->value,
                    'label' => $order->payment_method->label(),
                ],
                'proof_url' => $order->payment_proof_path
                    ? Storage::disk('public')->url($order->payment_proof_path)
                    : null,
                'confirmed_at' => $order->payment_confirmed_at?->toIso8601String(),
                'rejection_reason' => $order->payment_rejection_reason,
            ],
            'items_count' => $order->items_count,
            'items' => $items
                ->map(fn (OrderItem $item) => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal,
                    'is_pre_order' => $item->is_pre_order,
                    'pre_order_estimate_days' => $item->pre_order_estimate_days,
                    'pre_order_deadline' => $item->pre_order_deadline?->toDateString(),
                    'pre_order_min_quantity' => $item->pre_order_min_quantity,
                    'pre_order_note' => $item->pre_order_note,
                    'status' => [
                        'code' => $item->status->value,
                        'label' => $item->status->label(),
                    ],
                    'payment' => [
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
                    ],
                    'seller' => $this->ownerPayload($item),
                ])
                ->values()
                ->all(),
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{id: int, name: string}
     */
    private function ownerPayload(OrderItem $item): array
    {
        if ($item->product->seller) {
            return ['id' => $item->product->seller->id, 'name' => $item->product->seller->name];
        }

        return ['id' => $item->product->upJurusan->id, 'name' => $item->product->upJurusan->name];
    }
}
