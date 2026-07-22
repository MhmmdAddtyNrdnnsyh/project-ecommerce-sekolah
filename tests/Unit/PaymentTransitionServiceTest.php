<?php

use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\PaymentTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makePaymentItem(array $overrides = []): array
{
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create(['stock' => 5]);
    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'payment_status' => PaymentStatus::Unpaid,
    ]);
    $item = OrderItem::factory()->create(array_merge([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
    ], $overrides));

    $product->update(['stock' => 3]);

    return compact('seller', 'buyer', 'product', 'order', 'item');
}

test('allowedTargets encodes formal payment state machine', function () {
    expect(PaymentTransitionService::allowedTargets(PaymentStatus::Unpaid))
        ->toBe([PaymentStatus::Paid, PaymentStatus::Rejected])
        ->and(PaymentTransitionService::allowedTargets(PaymentStatus::PendingConfirmation))
        ->toBe([PaymentStatus::Paid, PaymentStatus::Rejected])
        ->and(PaymentTransitionService::allowedTargets(PaymentStatus::Paid))
        ->toBe([])
        ->and(PaymentTransitionService::allowedTargets(PaymentStatus::Rejected))
        ->toBe([])
        ->and(PaymentStatus::Paid->isTerminal())->toBeTrue()
        ->and(PaymentStatus::Rejected->isTerminal())->toBeTrue()
        ->and(PaymentStatus::Unpaid->isTerminal())->toBeFalse();
});

test('approve transitions unpaid to paid and syncs order payment', function () {
    ['seller' => $seller, 'order' => $order, 'item' => $item] = makePaymentItem();

    PaymentTransitionService::approve($item, $seller);

    $item->refresh();
    $order->refresh();

    expect($item->payment_status)->toBe(PaymentStatus::Paid)
        ->and($item->payment_confirmed_by)->toBe($seller->id)
        ->and($item->payment_confirmed_at)->not->toBeNull()
        ->and($order->payment_status)->toBe(PaymentStatus::Paid);
});

test('reject transitions unpaid to rejected and cancels with restock', function () {
    ['seller' => $seller, 'product' => $product, 'order' => $order, 'item' => $item] = makePaymentItem();

    PaymentTransitionService::reject($item, $seller, 'Tunai tidak diterima.');

    $item->refresh();
    $order->refresh();
    $product->refresh();

    expect($item->payment_status)->toBe(PaymentStatus::Rejected)
        ->and($item->payment_rejection_reason)->toBe('Tunai tidak diterima.')
        ->and($item->status)->toBe(OrderItemStatus::Cancelled)
        ->and($item->cancel_reason)->toBe('Tunai tidak diterima.')
        ->and($product->stock)->toBe(5)
        ->and($order->payment_status)->toBe(PaymentStatus::Rejected);
});

test('cannot approve paid or rejected items', function () {
    ['seller' => $seller, 'item' => $paid] = makePaymentItem([
        'payment_status' => PaymentStatus::Paid,
    ]);

    expect(fn () => PaymentTransitionService::approve($paid, $seller))
        ->toThrow(ValidationException::class);

    ['seller' => $seller2, 'item' => $rejected] = makePaymentItem([
        'payment_status' => PaymentStatus::Rejected,
    ]);

    expect(fn () => PaymentTransitionService::approve($rejected, $seller2))
        ->toThrow(ValidationException::class);
});

test('cannot reject paid or rejected items', function () {
    ['seller' => $seller, 'item' => $paid] = makePaymentItem([
        'payment_status' => PaymentStatus::Paid,
    ]);

    expect(fn () => PaymentTransitionService::reject($paid, $seller, 'x'))
        ->toThrow(ValidationException::class);

    ['seller' => $seller2, 'item' => $rejected] = makePaymentItem([
        'payment_status' => PaymentStatus::Rejected,
        'status' => OrderItemStatus::Cancelled,
    ]);

    expect(fn () => PaymentTransitionService::reject($rejected, $seller2, 'x'))
        ->toThrow(ValidationException::class);
});

test('cannot reject terminal completed item', function () {
    ['seller' => $seller, 'item' => $item] = makePaymentItem([
        'status' => OrderItemStatus::Completed,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    expect(fn () => PaymentTransitionService::reject($item, $seller, 'x'))
        ->toThrow(ValidationException::class);
});
