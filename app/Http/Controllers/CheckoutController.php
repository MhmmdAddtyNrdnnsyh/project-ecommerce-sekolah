<?php

namespace App\Http\Controllers;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\UpJurusanConsignmentStatus;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\UpJurusanConsignment;
use App\Models\UpJurusanStockMovement;
use App\Models\User;
use App\Support\OrderItemCancellation;
use App\Support\PreOrderRules;
use App\Support\TransactionCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function confirm(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $selectedIds = $this->selectedCartItemIds($request);
        $items = $request->query('product')
            ? collect([$this->buyNowItemPayload($request)])
            : CartItem::query()
                ->with([
                    'product.category:id,name,slug',
                    'product.seller:id,name',
                    'product.upJurusan:id,name',
                    'product.upJurusanConsignments.upJurusan:id,name',
                ])
                ->where('user_id', $user->id)
                ->when($selectedIds !== [], fn ($query) => $query->whereIn('id', $selectedIds))
                ->latest()
                ->get()
                ->map(function (CartItem $cartItem) {
                    $invalidReasons = PreOrderRules::invalidReasons($cartItem->product, $cartItem->quantity);

                    return [
                        'id' => $cartItem->id,
                        'source' => 'cart',
                        'quantity' => $cartItem->quantity,
                        'subtotal' => $cartItem->quantity * $cartItem->product->price,
                        'is_valid' => $invalidReasons === [],
                        'invalid_reasons' => $invalidReasons,
                        'product' => $this->productPayload($cartItem->product),
                    ];
                })
                ->values();

        return Inertia::render('checkout/confirm', [
            'items' => $items->all(),
            'summary' => [
                'total_items' => $items->sum('quantity'),
                'total_price' => $items->sum('subtotal'),
                'has_invalid_items' => $items->contains(fn (array $item) => ($item['is_valid'] ?? true) === false),
            ],
        ]);
    }

    public function __invoke(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $selectedIds = $this->selectedCartItemIds($request);
        $request->merge([
            'pickup_method' => $request->input('pickup_method', 'pickup'),
            'payment_method' => $request->input('payment_method', PaymentMethod::Cash->value),
        ]);
        $validated = $request->validate([
            'pickup_method' => ['required', 'string', 'in:pickup,delivery'],
            'pickup_location' => ['required_if:pickup_method,delivery', 'nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'string', 'in:cash'],
            'buy_now_product_id' => ['nullable', 'integer', 'exists:products,id'],
            'buy_now_quantity' => ['required_with:buy_now_product_id', 'nullable', 'integer', 'min:1'],
        ]);

        $order = DB::transaction(function () use ($user, $validated, $selectedIds) {
            $order = Order::query()->create([
                'code' => TransactionCode::make(),
                'user_id' => $user->id,
                'status' => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Unpaid,
                'payment_method' => PaymentMethod::Cash,
                'total_price' => 0,
                'pickup_method' => $validated['pickup_method'],
                'pickup_location' => $validated['pickup_method'] === 'delivery'
                    ? $validated['pickup_location'] ?? null
                    : null,
                'expires_at' => now()->addHours(OrderItemCancellation::UNPAID_EXPIRY_HOURS),
            ]);
            $totalPrice = 0;

            if (isset($validated['buy_now_product_id'])) {
                $productId = (int) $validated['buy_now_product_id'];
                $product = Product::query()
                    ->lockForUpdate()
                    ->findOrFail($productId);
                $quantity = (int) $validated['buy_now_quantity'];

                $totalPrice = $this->createOrderItem($order, $product, $quantity, $user);

                $order->update(['total_price' => $totalPrice]);

                return $order;
            }

            $cartItems = CartItem::query()
                ->where('user_id', $user->id)
                ->when($selectedIds !== [], fn ($query) => $query->whereIn('id', $selectedIds))
                ->orderBy('id')
                ->get();

            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'Cart masih kosong.',
                ]);
            }

            foreach ($cartItems as $cartItem) {
                $product = Product::query()
                    ->lockForUpdate()
                    ->findOrFail($cartItem->product_id);

                $totalPrice += $this->createOrderItem($order, $product, $cartItem->quantity, $user);
            }

            $order->update([
                'total_price' => $totalPrice,
            ]);

            CartItem::query()
                ->where('user_id', $user->id)
                ->when($selectedIds !== [], fn ($query) => $query->whereIn('id', $selectedIds))
                ->delete();

            return $order;
        });

        return to_route('orders.show', $order)->with('success', 'Pesanan berhasil dibuat.');
    }

    /**
     * @return array<int, int>
     */
    private function selectedCartItemIds(Request $request): array
    {
        $items = $request->has('selected_cart_item_ids')
            ? $request->input('selected_cart_item_ids', [])
            : $request->query('items', []);

        if (is_string($items)) {
            $items = explode(',', $items);
        }

        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->map(fn ($item) => (int) $item)
            ->filter(fn (int $item) => $item > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function buyNowItemPayload(Request $request): array
    {
        $product = Product::query()
            ->with([
                'category:id,name,slug',
                'seller:id,name',
                'upJurusan:id,name',
                'upJurusanConsignments.upJurusan:id,name',
            ])
            ->where('slug', $request->query('product'))
            ->firstOrFail();
        abort_unless($product->status === ProductStatus::Approved, 404);

        $quantity = max(1, (int) $request->integer('quantity', 1));

        return [
            'id' => $product->id,
            'source' => 'buy_now',
            'quantity' => $quantity,
            'subtotal' => $quantity * $product->price,
            'product' => $this->productPayload($product),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function productPayload(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => $product->price,
            'stock' => $product->availableStock(),
            'is_pre_order' => $product->isPreOrder(),
            'pre_order_estimate_days' => $product->pre_order_estimate_days,
            'pre_order_deadline' => $product->pre_order_deadline?->toDateString(),
            'pre_order_min_quantity' => $product->pre_order_min_quantity,
            'pre_order_note' => $product->pre_order_note,
            'image' => $product->image,
            'seller' => $this->ownerPayload($product),
            'category' => [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ],
            'pickup_place' => $this->pickupPlacePayload($product),
        ];
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private function pickupPlacePayload(Product $product): ?array
    {
        $pickupPlace = $product->upJurusanConsignments
            ->first()
            ?->upJurusan;

        return $pickupPlace ? [
            'id' => $pickupPlace->id,
            'name' => $pickupPlace->name,
        ] : null;
    }

    private function createOrderItem(Order $order, Product $product, int $quantity, User $actor): int
    {
        if ($product->status !== ProductStatus::Approved) {
            throw ValidationException::withMessages([
                'cart' => "Produk {$product->name} tidak tersedia untuk checkout.",
            ]);
        }

        if (! $product->isPreOrder() && $quantity > $product->availableStock()) {
            throw ValidationException::withMessages([
                'cart' => "Quantity {$product->name} melebihi stok tersedia.",
            ]);
        }

        PreOrderRules::assertPurchasableForCheckout($product, $quantity);

        $subtotal = $quantity * $product->price;

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'status' => OrderItemStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'payment_method' => PaymentMethod::Cash,
            'is_pre_order' => $product->isPreOrder(),
            'pre_order_estimate_days' => $product->isPreOrder() ? $product->pre_order_estimate_days : null,
            'pre_order_deadline' => $product->isPreOrder() ? $product->pre_order_deadline?->toDateString() : null,
            'pre_order_min_quantity' => $product->isPreOrder() ? $product->pre_order_min_quantity : null,
            'pre_order_note' => $product->isPreOrder() ? $product->pre_order_note : null,
        ]);

        if ($product->isPreOrder()) {
            return $subtotal;
        }

        if ($product->usesConsignmentStock()) {
            $this->recordConsignmentSale($order, $product, $actor, $quantity);
        } else {
            $product->update([
                'stock' => $product->stock - $quantity,
            ]);

            if ($product->seller_id === null && $product->up_jurusan_id !== null) {
                UpJurusanStockMovement::query()->create([
                    'up_jurusan_consignment_id' => null,
                    'product_id' => $product->id,
                    'order_id' => $order->id,
                    'user_id' => $actor->id,
                    'type' => 'out',
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'gross_amount' => $subtotal,
                    'commission_amount' => $subtotal,
                    'seller_amount' => 0,
                ]);
            }
        }

        return $subtotal;
    }

    private function recordConsignmentSale(Order $order, Product $product, User $actor, int $quantity): void
    {
        $remaining = $quantity;
        $consignments = UpJurusanConsignment::query()
            ->where('product_id', $product->id)
            ->whereColumn('received_quantity', '>', 'sold_quantity')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($consignments as $consignment) {
            if ($remaining <= 0) {
                return;
            }

            $available = $consignment->received_quantity - $consignment->sold_quantity;
            $sold = min($remaining, $available);
            $grossAmount = $product->price * $sold;
            $commissionAmount = intdiv($grossAmount * $consignment->commission_rate, 100);

            $consignment->update([
                'sold_quantity' => $consignment->sold_quantity + $sold,
                'status' => $consignment->sold_quantity + $sold >= $consignment->received_quantity
                    ? UpJurusanConsignmentStatus::Completed
                    : $consignment->status,
            ]);
            UpJurusanStockMovement::query()->create([
                'up_jurusan_consignment_id' => $consignment->id,
                'product_id' => null,
                'order_id' => $order->id,
                'user_id' => $actor->id,
                'type' => 'out',
                'quantity' => $sold,
                'unit_price' => $product->price,
                'gross_amount' => $grossAmount,
                'commission_amount' => $commissionAmount,
                'seller_amount' => $grossAmount - $commissionAmount,
            ]);

            $remaining -= $sold;
        }

        if ($remaining > 0) {
            throw ValidationException::withMessages([
                'cart' => "Quantity {$product->name} melebihi stok tersedia.",
            ]);
        }
    }

    /**
     * @return array{id: int, name: string}
     */
    private function ownerPayload(Product $product): array
    {
        if ($product->seller) {
            return ['id' => $product->seller->id, 'name' => $product->seller->name];
        }

        return ['id' => $product->upJurusan->id, 'name' => $product->upJurusan->name];
    }
}
