<?php

namespace App\Http\Controllers;

use App\Enums\OrderItemStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
                ->with('items:id,order_id,status')
                ->lockForUpdate()
                ->findOrFail($order->id);

            $sentItems = $current->items
                ->filter(fn (OrderItem $item) => $item->status === OrderItemStatus::Sent);

            if ($sentItems->isEmpty()) {
                abort(422, 'Tidak ada item yang sedang dikirim untuk diselesaikan.');
            }

            OrderItem::query()
                ->whereIn('id', $sentItems->pluck('id'))
                ->update(['status' => OrderItemStatus::Completed]);
        });

        return to_route('orders.show', $order)
            ->with('success', 'Pesanan berhasil ditandai selesai.');
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(Order $order, ?int $itemLimit = null): array
    {
        $status = $this->summaryStatus($order);
        $items = $itemLimit === null ? $order->items : $order->items->take($itemLimit);

        return [
            'id' => $order->id,
            'code' => $order->code ?? "TRX-{$order->id}",
            'status' => [
                'code' => $status->value,
                'label' => $status->label(),
            ],
            'can_complete' => $order->items->contains(fn (OrderItem $item) => $item->status === OrderItemStatus::Sent),
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

    private function summaryStatus(Order $order): OrderItemStatus
    {
        $statuses = $order->items->pluck('status');

        if ($statuses->isEmpty()) {
            return OrderItemStatus::Pending;
        }

        if ($statuses->contains(OrderItemStatus::Pending)) {
            return OrderItemStatus::Pending;
        }

        if ($statuses->contains(OrderItemStatus::InProduction)) {
            return OrderItemStatus::InProduction;
        }

        if ($statuses->contains(OrderItemStatus::Ready)) {
            return OrderItemStatus::Ready;
        }

        if ($statuses->contains(OrderItemStatus::Packed)) {
            return OrderItemStatus::Packed;
        }

        if ($statuses->contains(OrderItemStatus::Sent)) {
            return OrderItemStatus::Sent;
        }

        return OrderItemStatus::Completed;
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
