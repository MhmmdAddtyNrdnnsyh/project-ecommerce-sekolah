<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($user) {
            $cartItems = CartItem::query()
                ->where('user_id', $user->id)
                ->orderBy('id')
                ->get();

            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'Cart masih kosong.',
                ]);
            }

            $order = Order::query()->create([
                'user_id' => $user->id,
                'status' => OrderStatus::Pending,
                'total_price' => 0,
            ]);
            $totalPrice = 0;

            foreach ($cartItems as $cartItem) {
                $product = Product::query()
                    ->lockForUpdate()
                    ->findOrFail($cartItem->product_id);

                if ($product->status !== ProductStatus::Approved) {
                    throw ValidationException::withMessages([
                        'cart' => "Produk {$product->name} tidak tersedia untuk checkout.",
                    ]);
                }

                if ($cartItem->quantity > $product->stock) {
                    throw ValidationException::withMessages([
                        'cart' => "Quantity {$product->name} melebihi stok tersedia.",
                    ]);
                }

                $subtotal = $cartItem->quantity * $product->price;
                $totalPrice += $subtotal;

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $product->price,
                    'quantity' => $cartItem->quantity,
                    'subtotal' => $subtotal,
                ]);

                $product->update([
                    'stock' => $product->stock - $cartItem->quantity,
                ]);
            }

            $order->update([
                'total_price' => $totalPrice,
            ]);

            CartItem::query()
                ->where('user_id', $user->id)
                ->delete();
        });

        return to_route('cart.index');
    }
}
