<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Support\OrderPaymentSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function approvePayment(Request $request, Order $order): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        DB::transaction(function () use ($order, $admin) {
            /** @var Order $current */
            $current = Order::query()
                ->with('items:id,order_id,payment_status')
                ->lockForUpdate()
                ->findOrFail($order->id);

            abort_unless($current->payment_status !== PaymentStatus::Paid, 422);

            $current->items()->update([
                'payment_status' => PaymentStatus::Paid->value,
                'payment_confirmed_at' => now(),
                'payment_confirmed_by' => $admin->id,
                'payment_rejection_reason' => null,
            ]);

            OrderPaymentSync::sync($current);
        });

        return to_route('admin.orders.index')
            ->with('success', "Pembayaran order {$order->code} berhasil di-override lunas.");
    }

    public function rejectPayment(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'payment_rejection_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        DB::transaction(function () use ($order, $validated) {
            /** @var Order $current */
            $current = Order::query()
                ->with('items:id,order_id,payment_status')
                ->lockForUpdate()
                ->findOrFail($order->id);

            abort_unless($current->payment_status !== PaymentStatus::Paid, 422);

            $current->items()
                ->where('payment_status', '!=', PaymentStatus::Paid->value)
                ->update([
                    'payment_status' => PaymentStatus::Rejected->value,
                    'payment_confirmed_at' => null,
                    'payment_confirmed_by' => null,
                    'payment_rejection_reason' => $validated['payment_rejection_reason'],
                ]);

            OrderPaymentSync::sync($current);
        });

        return to_route('admin.orders.index')
            ->with('success', "Pembayaran order {$order->code} ditolak.");
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
