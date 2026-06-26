<?php

namespace App\Http\Controllers;

use App\Enums\OrderItemStatus;
use App\Http\Requests\Seller\UpdateOrderItemStatusRequest;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
                'order:id,user_id,created_at',
                'order.user:id,name',
                'product:id,name,slug,seller_id',
            ])
            ->whereHas('product', fn ($q) => $q->where('seller_id', $seller->id));

        if ($search = $validated['q'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('order', function ($oq) use ($search) {
                    $oq->where('id', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
                })
                    ->orWhere('product_name', 'like', "%{$search}%");
            });
        }

        if ($status = $validated['status'] ?? null) {
            $query->where('status', $status);
        }

        $perPage = 10;

        $orderItems = $query->latest('order_items.created_at')
            ->paginate($perPage)
            ->withQueryString();

        /** @var LengthAwarePaginator<int, OrderItem> $orderItems */

        return Inertia::render('seller/orders/index', [
            'orderItems' => $orderItems->through(function (OrderItem $item): array {
                return [
                    'id' => $item->id,
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
                    'product_name' => $item->product_name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal,
                    'status' => [
                        'code' => $item->status->value,
                        'label' => $item->status->label(),
                    ],
                    'created_at' => $item->created_at?->toIso8601String(),
                ];
            }),
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
            'order:id,user_id,created_at',
            'order.user:id,name',
            'product:id,name,slug,seller_id,category_id',
            'product.category:id,name,slug',
        ]);

        /** @var OrderItem $orderItem */

        return Inertia::render('seller/orders/show', [
            'orderItem' => [
                'id' => $orderItem->id,
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
                'product_name' => $orderItem->product_name,
                'price' => $orderItem->price,
                'quantity' => $orderItem->quantity,
                'subtotal' => $orderItem->subtotal,
                'status' => [
                    'code' => $orderItem->status->value,
                    'label' => $orderItem->status->label(),
                ],
                'created_at' => $orderItem->created_at?->toIso8601String(),
            ],
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

        DB::transaction(function () use ($orderItem, $newStatus) {
            /** @var OrderItem $current */
            $current = OrderItem::query()
                ->lockForUpdate()
                ->findOrFail($orderItem->id);

            $expectedNext = $current->status->next();

            if ($expectedNext === null || $newStatus !== $expectedNext) {
                throw ValidationException::withMessages([
                    'status' => 'Status tidak valid. Perubahan harus berurutan: pending, dikemas, lalu dikirim.',
                ]);
            }

            $current->update(['status' => $newStatus]);
        });

        return to_route('seller.orders.index')
            ->with('success', 'Status pesanan berhasil diperbarui.');
    }
}
