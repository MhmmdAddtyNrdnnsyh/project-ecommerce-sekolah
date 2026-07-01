<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AdminOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Order::query()
            ->with([
                'user:id,name,email',
                'items.product:id,name,slug,seller_id,up_jurusan_id',
                'items.product.seller:id,name',
                'items.product.upJurusan:id,name',
            ])
            ->withCount('items');

        if ($search = $validated['q'] ?? null) {
            $query->where(function ($q) use ($search) {
                $looksLikeCode = str_contains($search, '-');

                $q->when(is_numeric($search), fn ($query) => $query->where('id', (int) $search))
                    ->when($looksLikeCode, fn ($query) => $query->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($user) => $user
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('items', fn ($item) => $item
                        ->where('product_name', 'like', "%{$search}%"));
            });
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('admin/orders/index', [
            'orders' => $orders->through(fn (Order $order) => [
                'id' => $order->id,
                'code' => $order->code ?? "TRX-{$order->id}",
                'status' => [
                    'code' => $order->status->value,
                    'label' => $order->status->label(),
                ],
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
                'buyer' => [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                ],
                'items' => $order->items
                    ->take(3)
                    ->map(fn (OrderItem $item) => [
                        'id' => $item->id,
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'subtotal' => $item->subtotal,
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
                        ],
                        'seller' => $this->ownerPayload($item),
                    ])
                    ->values()
                    ->all(),
                'created_at' => $order->created_at?->toIso8601String(),
            ]),
            'filters' => [
                'q' => $validated['q'] ?? '',
            ],
        ]);
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
