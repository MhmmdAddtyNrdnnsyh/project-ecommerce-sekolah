<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
            ->map(fn (CartItem $cartItem) => [
                'id' => $cartItem->id,
                'quantity' => $cartItem->quantity,
                'subtotal' => $cartItem->quantity * $cartItem->product->price,
                'product' => [
                    'id' => $cartItem->product->id,
                    'name' => $cartItem->product->name,
                    'slug' => $cartItem->product->slug,
                    'price' => $cartItem->product->price,
                    'stock' => $cartItem->product->availableStock(),
                    'image' => $cartItem->product->image,
                    'seller' => $this->ownerPayload($cartItem->product),
                    'category' => [
                        'id' => $cartItem->product->category->id,
                        'name' => $cartItem->product->category->name,
                        'slug' => $cartItem->product->category->slug,
                    ],
                ],
            ])
            ->values();

        return Inertia::render('cart/index', [
            'items' => $items->all(),
            'summary' => [
                'total_items' => $items->sum('quantity'),
                'total_price' => $items->sum('subtotal'),
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

        $this->ensureQuantityDoesNotExceedStock($nextQuantity, $product->availableStock());

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

        $this->ensureQuantityDoesNotExceedStock($quantity, $cartItem->product->availableStock());

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

    private function ensureQuantityDoesNotExceedStock(int $quantity, int $stock): void
    {
        if ($quantity <= $stock) {
            return;
        }

        throw ValidationException::withMessages([
            'quantity' => 'Quantity tidak boleh melebihi stok tersedia.',
        ]);
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
