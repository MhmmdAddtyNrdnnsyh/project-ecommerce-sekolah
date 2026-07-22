<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Support\PurchasableProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $items = CartItem::query()
            ->with(['product.category:id,name,slug', 'product.seller:id,name', 'product.upJurusan:id,name'])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function (CartItem $cartItem) {
                $product = $cartItem->product;
                $invalidReasons = PurchasableProductService::invalidReasonCodes($product, $cartItem->quantity);

                return [
                    'id' => $cartItem->id,
                    'quantity' => $cartItem->quantity,
                    'subtotal' => $product ? $cartItem->quantity * $product->price : 0,
                    'is_valid' => $invalidReasons === [],
                    'invalid_reasons' => $invalidReasons,
                    'product' => $product ? [
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
                    ] : null,
                ];
            })
            ->values();

        return Inertia::render('cart/index', [
            'items' => $items->all(),
            'summary' => [
                'total_items' => $items->sum('quantity'),
                'total_price' => $items->sum('subtotal'),
                'has_invalid_items' => $items->contains(fn (array $item) => $item['is_valid'] === false),
            ],
        ]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === ProductStatus::Approved, 404);

        /** @var User $user */
        $user = $request->user();
        $quantity = $this->validatedQuantity($request);
        $cartItem = CartItem::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();
        $nextQuantity = $quantity + ($cartItem->quantity ?? 0);

        PurchasableProductService::assertPurchasable($product, $nextQuantity);

        if ($cartItem) {
            $cartItem->update(['quantity' => $nextQuantity]);
        } else {
            $cartItem = CartItem::query()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
            ]);
        }

        if ($request->input('redirect_to') === 'checkout.confirm') {
            return to_route('checkout.confirm', ['items' => (string) $cartItem->id]);
        }

        return back(302, [], route('cart.index'))->with('success', 'Produk ditambahkan ke keranjang.');
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($cartItem->user_id === $user->id, 404);

        $cartItem->load('product');
        $quantity = $this->validatedQuantity($request);

        PurchasableProductService::assertPurchasable($cartItem->product, $quantity);

        $cartItem->update(['quantity' => $quantity]);

        return to_route('cart.index');
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($cartItem->user_id === $user->id, 404);

        $cartItem->delete();

        return to_route('cart.index');
    }

    private function validatedQuantity(Request $request): int
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        return (int) $validated['quantity'];
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
