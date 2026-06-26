<?php

namespace App\Http\Controllers;

use App\Enums\OrderItemStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
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

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(Order $order, ?int $itemLimit = null): array
    {
        $status = $this->summaryStatus($order);
        $items = $itemLimit === null ? $order->items : $order->items->take($itemLimit);

        return [
            'id' => $order->id,
            'status' => [
                'code' => $status->value,
                'label' => $status->label(),
            ],
            'total_price' => $order->total_price,
            'items_count' => $order->items_count,
            'items' => $items
                ->map(fn (OrderItem $item) => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal,
                    'status' => [
                        'code' => $item->status->value,
                        'label' => $item->status->label(),
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

        if ($statuses->contains(OrderItemStatus::Packed)) {
            return OrderItemStatus::Packed;
        }

        return OrderItemStatus::Sent;
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
