<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Support\OrderLivenessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminOrderController extends Controller
{
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $this->authorize('forceCancel', $order);

        $validated = $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        OrderLivenessService::forceCancel(
            $order,
            $admin,
            $validated['cancel_reason'] ?? 'Dibatalkan oleh admin',
        );

        return back()->with('success', 'Pesanan berhasil dibatalkan.');
    }

    public function forceComplete(Request $request, Order $order): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $this->authorize('forceComplete', $order);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        OrderLivenessService::forceComplete(
            $order,
            $admin,
            $validated['reason'] ?? 'Diselesaikan paksa oleh admin',
        );

        return back()->with('success', 'Pesanan berhasil diselesaikan oleh admin.');
    }

    public function markReview(Request $request, Order $order): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $this->authorize('markManualReview', $order);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        OrderLivenessService::markRequiresManualReview(
            $order,
            $admin,
            $validated['reason'] ?? null,
        );

        return back()->with('success', 'Pesanan ditandai butuh peninjauan manual.');
    }

    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'liveness' => ['nullable', 'string', Rule::in(OrderLivenessService::filterValues())],
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

        OrderLivenessService::applyFilter($query, $validated['liveness'] ?? null);

        $orders = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('admin/orders/index', [
            'orders' => $orders->through(fn (Order $order) => [
                'id' => $order->id,
                'code' => $order->code ?? "TRX-{$order->id}",
                'status' => [
                    'code' => $order->status->value,
                    'label' => $order->status->label(),
                ],
                'liveness' => [
                    'code' => OrderLivenessService::livenessLabel($order),
                    'reasons' => OrderLivenessService::stuckReasonsFor($order),
                    'requires_manual_review' => (bool) $order->requires_manual_review,
                    'requires_manual_review_reason' => $order->requires_manual_review_reason,
                    'stuck_detected_at' => $order->stuck_detected_at?->toIso8601String(),
                    'expires_at' => $order->expires_at?->toIso8601String(),
                ],
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
                'buyer' => [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                ],
                'total_price' => $order->total_price,
                'items_count' => $order->items_count,
                'items' => $order->items
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
                'liveness' => $validated['liveness'] ?? '',
            ],
            'livenessOptions' => collect(OrderLivenessService::filterValues())
                ->map(fn (string $value) => [
                    'code' => $value,
                    'label' => match ($value) {
                        'active' => 'Aktif',
                        'expired' => 'Expired unpaid',
                        'stuck' => 'Stuck',
                        'requires_action' => 'Butuh tindakan',
                        default => $value,
                    },
                ])
                ->values()
                ->all(),
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
