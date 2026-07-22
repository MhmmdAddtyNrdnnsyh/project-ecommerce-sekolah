<?php

use App\Enums\ProductStatus;
use App\Enums\UpJurusanConsignmentStatus;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\UpJurusanStockMovement;
use App\Models\User;
use App\Support\ConsignmentTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeConsignment(array $overrides = []): UpJurusanConsignment
{
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->create([
        'status' => ProductStatus::Pending,
        'stock' => 0,
    ]);
    $up = UpJurusan::factory()->create();

    return UpJurusanConsignment::factory()->create(array_merge([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $up->id,
        'requested_quantity' => 10,
        'received_quantity' => 0,
        'sold_quantity' => 0,
        'commission_rate' => null,
        'status' => UpJurusanConsignmentStatus::PendingApproval,
    ], $overrides));
}

test('allowedTargets encodes formal consignment state machine', function () {
    expect(ConsignmentTransitionService::allowedTargets(UpJurusanConsignmentStatus::PendingApproval))
        ->toBe([
            UpJurusanConsignmentStatus::Approved,
            UpJurusanConsignmentStatus::Rejected,
        ])
        ->and(ConsignmentTransitionService::allowedTargets(UpJurusanConsignmentStatus::Approved))
        ->toBe([
            UpJurusanConsignmentStatus::Received,
            UpJurusanConsignmentStatus::Cancelled,
        ])
        ->and(ConsignmentTransitionService::allowedTargets(UpJurusanConsignmentStatus::Received))
        ->toBe([
            UpJurusanConsignmentStatus::Received,
            UpJurusanConsignmentStatus::Completed,
        ])
        ->and(ConsignmentTransitionService::allowedTargets(UpJurusanConsignmentStatus::Completed))
        ->toBe([])
        ->and(ConsignmentTransitionService::allowedTargets(UpJurusanConsignmentStatus::Cancelled))
        ->toBe([])
        ->and(ConsignmentTransitionService::allowedTargets(UpJurusanConsignmentStatus::Rejected))
        ->toBe([]);
});

test('approve transitions pending_approval to approved', function () {
    $consignment = makeConsignment();

    ConsignmentTransitionService::approve($consignment, 15);

    $consignment->refresh();
    expect($consignment->status)->toBe(UpJurusanConsignmentStatus::Approved)
        ->and($consignment->commission_rate)->toBe(15)
        ->and($consignment->product->fresh()->status)->toBe(ProductStatus::Approved);
});

test('reject transitions pending_approval to rejected', function () {
    $consignment = makeConsignment();

    ConsignmentTransitionService::reject($consignment, 'Tidak sesuai standar');

    $consignment->refresh();
    expect($consignment->status)->toBe(UpJurusanConsignmentStatus::Rejected)
        ->and($consignment->note)->toBe('Tidak sesuai standar')
        ->and($consignment->product->fresh()->status)->toBe(ProductStatus::Rejected);
});

test('cancel transitions approved to cancelled', function () {
    $consignment = makeConsignment([
        'status' => UpJurusanConsignmentStatus::Approved,
        'commission_rate' => 10,
    ]);

    ConsignmentTransitionService::cancel($consignment, 'Seller batal');

    expect($consignment->fresh()->status)->toBe(UpJurusanConsignmentStatus::Cancelled);
});

test('receive transitions approved to received and supports partial receive', function () {
    $actor = User::factory()->create(['role' => UserRole::PicketOfficer]);
    $consignment = makeConsignment([
        'status' => UpJurusanConsignmentStatus::Approved,
        'commission_rate' => 10,
        'requested_quantity' => 10,
    ]);

    ConsignmentTransitionService::receive($consignment, 4, $actor);
    $consignment->refresh();
    expect($consignment->status)->toBe(UpJurusanConsignmentStatus::Received)
        ->and($consignment->received_quantity)->toBe(4);

    ConsignmentTransitionService::receive($consignment, 3, $actor);
    $consignment->refresh();
    expect($consignment->status)->toBe(UpJurusanConsignmentStatus::Received)
        ->and($consignment->received_quantity)->toBe(7);

    expect(UpJurusanStockMovement::query()->where('type', 'in')->sum('quantity'))->toBe(7);
});

test('recordSold auto-completes when sold reaches received', function () {
    $consignment = makeConsignment([
        'status' => UpJurusanConsignmentStatus::Received,
        'commission_rate' => 10,
        'requested_quantity' => 10,
        'received_quantity' => 5,
        'sold_quantity' => 2,
    ]);

    ConsignmentTransitionService::recordSold($consignment, 3);

    $consignment->refresh();
    expect($consignment->sold_quantity)->toBe(5)
        ->and($consignment->status)->toBe(UpJurusanConsignmentStatus::Completed);
});

test('complete requires received fully sold', function () {
    $consignment = makeConsignment([
        'status' => UpJurusanConsignmentStatus::Received,
        'received_quantity' => 4,
        'sold_quantity' => 4,
    ]);

    ConsignmentTransitionService::complete($consignment);

    expect($consignment->fresh()->status)->toBe(UpJurusanConsignmentStatus::Completed);
});

test('cannot approve twice', function () {
    $consignment = makeConsignment([
        'status' => UpJurusanConsignmentStatus::Approved,
        'commission_rate' => 10,
    ]);

    ConsignmentTransitionService::approve($consignment, 20);
})->throws(ValidationException::class);

test('cannot reject after sold', function () {
    $consignment = makeConsignment([
        'status' => UpJurusanConsignmentStatus::Received,
        'received_quantity' => 5,
        'sold_quantity' => 2,
    ]);

    ConsignmentTransitionService::reject($consignment, 'Terlambat');
})->throws(ValidationException::class);

test('cannot receive after cancelled', function () {
    $actor = User::factory()->create(['role' => UserRole::PicketOfficer]);
    $consignment = makeConsignment([
        'status' => UpJurusanConsignmentStatus::Cancelled,
    ]);

    ConsignmentTransitionService::receive($consignment, 1, $actor);
})->throws(ValidationException::class);

test('cannot receive after rejected', function () {
    $actor = User::factory()->create(['role' => UserRole::PicketOfficer]);
    $consignment = makeConsignment([
        'status' => UpJurusanConsignmentStatus::Rejected,
    ]);

    ConsignmentTransitionService::receive($consignment, 1, $actor);
})->throws(ValidationException::class);

test('cannot cancel after sold', function () {
    $consignment = makeConsignment([
        'status' => UpJurusanConsignmentStatus::Received,
        'received_quantity' => 5,
        'sold_quantity' => 1,
    ]);

    ConsignmentTransitionService::cancel($consignment);
})->throws(ValidationException::class);

test('cannot complete without receive', function () {
    $consignment = makeConsignment([
        'status' => UpJurusanConsignmentStatus::Approved,
        'commission_rate' => 10,
        'received_quantity' => 0,
        'sold_quantity' => 0,
    ]);

    ConsignmentTransitionService::complete($consignment);
})->throws(ValidationException::class);

test('cannot receive more than requested', function () {
    $actor = User::factory()->create(['role' => UserRole::PicketOfficer]);
    $consignment = makeConsignment([
        'status' => UpJurusanConsignmentStatus::Approved,
        'commission_rate' => 10,
        'requested_quantity' => 5,
        'received_quantity' => 0,
    ]);

    ConsignmentTransitionService::receive($consignment, 6, $actor);
})->throws(ValidationException::class);

test('cannot record sold more than available received stock', function () {
    $consignment = makeConsignment([
        'status' => UpJurusanConsignmentStatus::Received,
        'received_quantity' => 3,
        'sold_quantity' => 2,
    ]);

    ConsignmentTransitionService::recordSold($consignment, 2);
})->throws(ValidationException::class);

test('assertInvariants rejects sold greater than received', function () {
    $consignment = makeConsignment([
        'status' => UpJurusanConsignmentStatus::Received,
        'received_quantity' => 2,
        'sold_quantity' => 5,
    ]);

    ConsignmentTransitionService::assertInvariants($consignment);
})->throws(ValidationException::class);

test('restoreSold reopens completed consignment to received', function () {
    $consignment = makeConsignment([
        'status' => UpJurusanConsignmentStatus::Completed,
        'received_quantity' => 5,
        'sold_quantity' => 5,
    ]);

    ConsignmentTransitionService::restoreSold($consignment, 2);

    $consignment->refresh();
    expect($consignment->sold_quantity)->toBe(3)
        ->and($consignment->status)->toBe(UpJurusanConsignmentStatus::Received);
});

test('illegal transitions from terminal states are rejected', function (UpJurusanConsignmentStatus $from, UpJurusanConsignmentStatus $to) {
    $consignment = makeConsignment(['status' => $from]);

    ConsignmentTransitionService::assertCanTransition($consignment, $to);
})->with([
    [UpJurusanConsignmentStatus::Completed, UpJurusanConsignmentStatus::Approved],
    [UpJurusanConsignmentStatus::Cancelled, UpJurusanConsignmentStatus::Received],
    [UpJurusanConsignmentStatus::Rejected, UpJurusanConsignmentStatus::Approved],
    [UpJurusanConsignmentStatus::PendingApproval, UpJurusanConsignmentStatus::Received],
    [UpJurusanConsignmentStatus::PendingApproval, UpJurusanConsignmentStatus::Completed],
    [UpJurusanConsignmentStatus::Approved, UpJurusanConsignmentStatus::Completed],
    [UpJurusanConsignmentStatus::Received, UpJurusanConsignmentStatus::Cancelled],
])->throws(ValidationException::class);
