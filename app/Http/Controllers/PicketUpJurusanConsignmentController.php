<?php

namespace App\Http\Controllers;

use App\Enums\OrderItemStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\UpJurusanConsignmentStatus;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\UpJurusanConsignment;
use App\Models\UpJurusanDailyReport;
use App\Models\UpJurusanDailyReportTransaction;
use App\Models\UpJurusanPosSale;
use App\Models\UpJurusanStockMovement;
use App\Models\User;
use App\Support\OrderPaymentSync;
use App\Support\TransactionCode;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PicketUpJurusanConsignmentController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->pos($request);
    }

    public function dashboard(Request $request): Response
    {
        return Inertia::render('picket/dashboard', $this->pageData($request));
    }

    public function pos(Request $request): Response
    {
        return Inertia::render('picket/up-jurusan/consignments/index', $this->pageData($request));
    }

    public function receiving(Request $request): Response
    {
        return Inertia::render('picket/receiving', $this->pageData($request));
    }

    public function orders(Request $request): Response
    {
        return Inertia::render('picket/orders', $this->pageData($request));
    }

    public function reports(Request $request): Response
    {
        return Inertia::render('picket/reports', $this->pageData($request));
    }

    public function receipt(Request $request, UpJurusanPosSale $sale): Response
    {
        /** @var User $picket */
        $picket = $request->user();

        abort_unless($sale->up_jurusan_id === $picket->up_jurusan_id, 403);

        $sale->load([
            'upJurusan:id,name',
            'user:id,name',
            'movements.product:id,name',
            'movements.consignment.product:id,name',
        ]);

        return Inertia::render('picket/receipt', [
            'sale' => [
                'id' => $sale->id,
                'code' => $sale->code,
                'sold_at' => $sale->created_at?->toIso8601String(),
                'total_quantity' => $sale->total_quantity,
                'total_amount' => $sale->total_amount,
                'up_jurusan' => [
                    'id' => $sale->upJurusan->id,
                    'name' => $sale->upJurusan->name,
                ],
                'picket' => [
                    'id' => $sale->user->id,
                    'name' => $sale->user->name,
                ],
                'items' => $sale->movements
                    ->map(function (UpJurusanStockMovement $movement) {
                        $product = $movement->up_jurusan_consignment_id === null
                            ? $movement->product
                            : $movement->consignment->product;

                        return [
                            'id' => $movement->id,
                            'product_name' => $product->name,
                            'source' => $movement->up_jurusan_consignment_id === null ? 'Produk UP' : 'Titipan Seller',
                            'quantity' => $movement->quantity,
                            'unit_price' => $movement->unit_price,
                            'subtotal' => $movement->gross_amount,
                        ];
                    })
                    ->values()
                    ->all(),
            ],
        ]);
    }

    /**
     * @return array{
     *     up_jurusan: array{id: int, name: string}|null,
     *     pos_products: array<int, array{id: int, source: string, seller_name: string, product_name: string, price: int, available_quantity: int}>,
     *     daily_report: array{date: string, status: array{code: string, label: string}, total_sold: int, total_revenue: int, submitted_at: string|null, items: array<int, array{id: int, code: string, receipt_url: string, sold_at: string|null, total_quantity: int, total_amount: int, commission_amount: int, seller_amount: int, products: array<int, array{product_name: string, source: string, quantity: int, unit_price: int, subtotal: int}>}>},
     *     consignments: array<int, array{id: int, seller_name: string, product_name: string, up_jurusan_name: string, requested_quantity: int, received_quantity: int, sold_quantity: int, status: array{code: string, label: string}}>,
     *     order_items: array<int, array{id: int, code: string, order_id: int, buyer_name: string, seller_name: string, product_name: string, quantity: int, subtotal: int, status: array{code: string, label: string}, created_at: string|null}>
     * }
     */
    private function pageData(Request $request): array
    {
        /** @var User $picket */
        $picket = $request->user();
        $picket->load('upJurusan:id,name');
        $today = now()->toDateString();
        $consignments = UpJurusanConsignment::query()
            ->with(['seller:id,name', 'product:id,name,price', 'upJurusan:id,name'])
            ->where('up_jurusan_id', $picket->up_jurusan_id)
            ->latest()
            ->get();
        $upProducts = Product::query()
            ->with('upJurusan:id,name')
            ->where('up_jurusan_id', $picket->up_jurusan_id)
            ->whereNull('seller_id')
            ->where('status', ProductStatus::Approved)
            ->where('stock', '>', 0)
            ->latest()
            ->get(['id', 'up_jurusan_id', 'name', 'price', 'stock']);
        $dailyReport = UpJurusanDailyReport::query()
            ->with(['transactions.items'])
            ->where('up_jurusan_id', $picket->up_jurusan_id)
            ->where('user_id', $picket->id)
            ->whereDate('report_date', $today)
            ->first();
        $dailySales = $dailyReport === null
            ? UpJurusanPosSale::query()
                ->with([
                    'movements.consignment.product:id,name,price',
                    'movements.product:id,name,price,up_jurusan_id',
                ])
                ->where('up_jurusan_id', $picket->up_jurusan_id)
                ->where('user_id', $picket->id)
                ->whereDate('created_at', $today)
                ->latest()
                ->get()
            : new Collection;
        $dailyReportSummary = $dailyReport === null
            ? [
                'total_sold' => $dailySales->sum('total_quantity'),
                'total_revenue' => $dailySales->sum('total_amount'),
                'submitted_at' => null,
                'items' => $this->dailyReportTransactions($dailySales),
            ]
            : [
                'total_sold' => $dailyReport->total_sold,
                'total_revenue' => $dailyReport->total_revenue,
                'submitted_at' => $dailyReport->submitted_at,
                'items' => $this->dailyReportTransactionSnapshots($dailyReport->transactions),
            ];
        $orderItems = OrderItem::query()
            ->with([
                'order:id,code,user_id,created_at',
                'order.user:id,name',
                'product:id,name,seller_id,up_jurusan_id',
                'product.seller:id,name',
                'product.upJurusan:id,name',
            ])
            ->whereHas('product', function ($query) use ($picket) {
                $query
                    ->where('up_jurusan_id', $picket->up_jurusan_id)
                    ->orWhereHas('upJurusanConsignments', fn ($query) => $query->where('up_jurusan_id', $picket->up_jurusan_id));
            })
            ->latest()
            ->limit(30)
            ->get();

        return [
            'up_jurusan' => $picket->upJurusan ? [
                'id' => $picket->upJurusan->id,
                'name' => $picket->upJurusan->name,
            ] : null,
            'pos_products' => $consignments
                ->filter(fn (UpJurusanConsignment $consignment) => $consignment->received_quantity > $consignment->sold_quantity)
                ->map(fn (UpJurusanConsignment $consignment) => [
                    'id' => $consignment->id,
                    'source' => 'consignment',
                    'seller_name' => $consignment->seller->name,
                    'product_name' => $consignment->product->name,
                    'price' => $consignment->product->price,
                    'available_quantity' => $consignment->received_quantity - $consignment->sold_quantity,
                ])
                ->concat($upProducts->map(fn (Product $product) => [
                    'id' => $product->id,
                    'source' => 'product',
                    'seller_name' => $product->upJurusan->name,
                    'product_name' => $product->name,
                    'price' => $product->price,
                    'available_quantity' => $product->stock,
                ]))
                ->values()
                ->all(),
            'daily_report' => [
                'date' => $today,
                'status' => [
                    'code' => $dailyReport === null ? 'open' : 'submitted',
                    'label' => $dailyReport === null ? 'Terbuka' : 'Dikirim',
                ],
                'total_sold' => $dailyReportSummary['total_sold'],
                'total_revenue' => $dailyReportSummary['total_revenue'],
                'submitted_at' => $dailyReportSummary['submitted_at'],
                'items' => $dailyReportSummary['items'],
            ],
            'consignments' => $consignments
                ->map(fn (UpJurusanConsignment $consignment) => [
                    'id' => $consignment->id,
                    'seller_name' => $consignment->seller->name,
                    'product_name' => $consignment->product->name,
                    'up_jurusan_name' => $consignment->upJurusan->name,
                    'requested_quantity' => $consignment->requested_quantity,
                    'received_quantity' => $consignment->received_quantity,
                    'sold_quantity' => $consignment->sold_quantity,
                    'status' => [
                        'code' => $consignment->status->value,
                        'label' => $consignment->status->label(),
                    ],
                ])
                ->all(),
            'order_items' => $orderItems
                ->map(function (OrderItem $item) {
                    $ownerName = $item->product->seller_id === null
                        ? $item->product->upJurusan->name
                        : $item->product->seller->name;

                    return [
                        'id' => $item->id,
                        'code' => $item->order->code ?? "TRX-{$item->order_id}",
                        'order_id' => $item->order_id,
                        'buyer_name' => $item->order->user->name,
                        'seller_name' => $ownerName,
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
                            'confirmed_at' => $item->payment_confirmed_at?->toIso8601String(),
                            'rejection_reason' => $item->payment_rejection_reason,
                        ],
                        'created_at' => $item->created_at?->toIso8601String(),
                    ];
                })
                ->all(),
        ];
    }

    public function receive(Request $request, UpJurusanConsignment $consignment): RedirectResponse
    {
        /** @var User $picket */
        $picket = $request->user();
        $this->authorizePicket($picket, $consignment);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);
        $quantity = (int) $validated['quantity'];
        $nextQuantity = $consignment->received_quantity + $quantity;

        if ($consignment->status !== UpJurusanConsignmentStatus::Approved && $consignment->status !== UpJurusanConsignmentStatus::Received) {
            throw ValidationException::withMessages([
                'quantity' => 'Barang hanya bisa diterima setelah request disetujui admin jurusan.',
            ]);
        }

        if ($nextQuantity > $consignment->requested_quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah diterima tidak boleh melebihi jumlah request.',
            ]);
        }

        DB::transaction(function () use ($consignment, $picket, $quantity, $nextQuantity) {
            $consignment->update([
                'received_quantity' => $nextQuantity,
                'status' => UpJurusanConsignmentStatus::Received,
            ]);

            UpJurusanStockMovement::query()->create([
                'up_jurusan_consignment_id' => $consignment->id,
                'user_id' => $picket->id,
                'type' => 'in',
                'quantity' => $quantity,
            ]);
        });

        return to_route('picket.dashboard')
            ->with('success', 'Barang titipan berhasil diterima.');
    }

    public function release(Request $request, UpJurusanConsignment $consignment): RedirectResponse
    {
        /** @var User $picket */
        $picket = $request->user();
        $this->authorizePicket($picket, $consignment);
        $this->ensureDailyReportIsOpen($picket);

        $quantity = $this->quantity($request);

        $saleCode = null;
        $saleId = 0;

        DB::transaction(function () use ($consignment, $picket, $quantity, &$saleCode, &$saleId) {
            $saleCode = $this->posSaleCode();
            $sale = UpJurusanPosSale::query()->create([
                'up_jurusan_id' => $picket->up_jurusan_id,
                'user_id' => $picket->id,
                'code' => $saleCode,
                'total_quantity' => $quantity,
                'total_amount' => 0,
            ]);
            $saleId = $sale->id;
            $totalAmount = $this->recordSale($picket, $consignment, $quantity, $sale);

            $sale->update(['total_amount' => $totalAmount]);
        });

        return to_route('picket.pos')
            ->with('success', "Barang keluar berhasil dicatat. No transaksi: {$saleCode}.")
            ->with('receipt_url', route('picket.pos.receipt', $saleId, absolute: false));
    }

    public function storeSale(Request $request): RedirectResponse
    {
        /** @var User $picket */
        $picket = $request->user();
        $this->ensureDailyReportIsOpen($picket);

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.source' => ['required', 'string', 'in:consignment,product'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $saleCode = null;
        $saleId = 0;

        DB::transaction(function () use ($validated, $picket, &$saleCode, &$saleId) {
            $sale = UpJurusanPosSale::query()->create([
                'up_jurusan_id' => $picket->up_jurusan_id,
                'user_id' => $picket->id,
                'code' => $this->posSaleCode(),
                'total_quantity' => 0,
                'total_amount' => 0,
            ]);
            $saleCode = $sale->code;
            $saleId = $sale->id;
            $totalQuantity = 0;
            $totalAmount = 0;

            foreach ($validated['items'] as $item) {
                if ($item['source'] === 'product') {
                    $product = Product::query()
                        ->whereKey($item['id'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    $this->authorizeProductPicket($picket, $product);
                    $totalAmount += $this->recordProductSale($picket, $product, (int) $item['quantity'], $sale);
                    $totalQuantity += (int) $item['quantity'];

                    continue;
                }

                $consignment = UpJurusanConsignment::query()
                    ->with('product:id,price')
                    ->whereKey($item['id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->authorizePicket($picket, $consignment);
                $totalAmount += $this->recordSale($picket, $consignment, (int) $item['quantity'], $sale);
                $totalQuantity += (int) $item['quantity'];
            }

            $sale->update([
                'total_quantity' => $totalQuantity,
                'total_amount' => $totalAmount,
            ]);
        });

        return to_route('picket.pos')
            ->with('success', "Penjualan berhasil dicatat. No transaksi: {$saleCode}.")
            ->with('receipt_url', route('picket.pos.receipt', $saleId, absolute: false));
    }

    public function storeReport(Request $request): RedirectResponse
    {
        /** @var User $picket */
        $picket = $request->user();
        $today = now()->toDateString();

        $sales = UpJurusanPosSale::query()
            ->with([
                'movements.consignment.product:id,name',
                'movements.product:id,name,up_jurusan_id',
            ])
            ->where('up_jurusan_id', $picket->up_jurusan_id)
            ->where('user_id', $picket->id)
            ->whereDate('created_at', $today)
            ->latest()
            ->get();

        if ($sales->isEmpty()) {
            throw ValidationException::withMessages([
                'report' => 'Belum ada penjualan hari ini.',
            ]);
        }

        DB::transaction(function () use ($picket, $sales, $today) {
            $existingReport = UpJurusanDailyReport::query()
                ->where('up_jurusan_id', $picket->up_jurusan_id)
                ->where('user_id', $picket->id)
                ->whereDate('report_date', $today)
                ->lockForUpdate()
                ->first();

            if ($existingReport !== null) {
                return;
            }

            $report = UpJurusanDailyReport::query()->create([
                'up_jurusan_id' => $picket->up_jurusan_id,
                'user_id' => $picket->id,
                'report_date' => $today,
                'total_sold' => $sales->sum('total_quantity'),
                'total_revenue' => $sales->sum('total_amount'),
                'submitted_at' => now(),
            ]);

            $this->snapshotReportTransactions($report, $sales);
        });

        return to_route('picket.reports')
            ->with('success', 'Laporan penjualan hari ini sudah dibuat.');
    }

    public function updateOrderStatus(Request $request, OrderItem $orderItem): RedirectResponse
    {
        /** @var User $picket */
        $picket = $request->user();

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:packed,sent'],
        ]);

        DB::transaction(function () use ($orderItem, $picket, $validated) {
            /** @var OrderItem $current */
            $current = OrderItem::query()
                ->with('product.upJurusanConsignments:id,product_id,up_jurusan_id')
                ->lockForUpdate()
                ->findOrFail($orderItem->id);

            $this->authorizeOrderItemPicket($picket, $current);

            $newStatus = OrderItemStatus::from($validated['status']);
            $expectedNext = $current->status->next();

            if ($expectedNext === null || $newStatus !== $expectedNext) {
                throw ValidationException::withMessages([
                    'status' => 'Status tidak valid. Picket hanya dapat mengubah status sampai dikirim.',
                ]);
            }

            $current->update(['status' => $newStatus]);
        });

        return to_route('picket.orders')
            ->with('success', 'Status pesanan berhasil diperbarui.');
    }

    public function approveOrderPayment(Request $request, OrderItem $orderItem): RedirectResponse
    {
        /** @var User $picket */
        $picket = $request->user();

        DB::transaction(function () use ($orderItem, $picket) {
            /** @var OrderItem $current */
            $current = OrderItem::query()
                ->with([
                    'order:id',
                    'product.upJurusanConsignments:id,product_id,up_jurusan_id',
                ])
                ->lockForUpdate()
                ->findOrFail($orderItem->id);

            $this->authorizeOrderItemPicket($picket, $current);

            if ($current->payment_status === PaymentStatus::Paid) {
                throw ValidationException::withMessages([
                    'payment' => 'Pembayaran item ini sudah lunas.',
                ]);
            }

            $current->update([
                'payment_status' => PaymentStatus::Paid,
                'payment_method' => PaymentMethod::Cash,
                'payment_confirmed_at' => now(),
                'payment_confirmed_by' => $picket->id,
                'payment_rejection_reason' => null,
            ]);

            OrderPaymentSync::sync($current->order);
        });

        return back()->with('success', 'Pelunasan item berhasil dikonfirmasi.');
    }

    private function quantity(Request $request): int
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        return (int) $validated['quantity'];
    }

    private function authorizePicket(User $picket, UpJurusanConsignment $consignment): void
    {
        abort_unless($picket->up_jurusan_id !== null && $consignment->up_jurusan_id === $picket->up_jurusan_id, 403);
    }

    private function authorizeProductPicket(User $picket, Product $product): void
    {
        abort_unless(
            $picket->up_jurusan_id !== null
            && $product->seller_id === null
            && $product->up_jurusan_id === $picket->up_jurusan_id
            && $product->status === ProductStatus::Approved,
            403,
        );
    }

    private function authorizeOrderItemPicket(User $picket, OrderItem $orderItem): void
    {
        abort_unless(
            $picket->up_jurusan_id !== null
            && (
                $orderItem->product->up_jurusan_id === $picket->up_jurusan_id
                || $orderItem->product->upJurusanConsignments->contains('up_jurusan_id', $picket->up_jurusan_id)
            ),
            403,
        );
    }

    private function ensureDailyReportIsOpen(User $picket): void
    {
        $reportSubmitted = UpJurusanDailyReport::query()
            ->where('up_jurusan_id', $picket->up_jurusan_id)
            ->whereDate('report_date', now()->toDateString())
            ->whereNotNull('submitted_at')
            ->exists();

        if ($reportSubmitted) {
            throw ValidationException::withMessages([
                'report' => 'Laporan hari ini sudah dikirim. Transaksi POS baru tidak bisa dicatat setelah laporan dikirim.',
            ]);
        }
    }

    private function recordSale(User $picket, UpJurusanConsignment $consignment, int $quantity, ?UpJurusanPosSale $sale = null): int
    {
        $available = $consignment->received_quantity - $consignment->sold_quantity;

        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah keluar tidak boleh melebihi stok titipan tersedia.',
            ]);
        }

        $nextSoldQuantity = $consignment->sold_quantity + $quantity;
        $unitPrice = $consignment->product->price;
        $grossAmount = $unitPrice * $quantity;
        $commissionAmount = intdiv($grossAmount * $consignment->commission_rate, 100);

        $consignment->update([
            'sold_quantity' => $nextSoldQuantity,
            'status' => $nextSoldQuantity >= $consignment->received_quantity
                ? UpJurusanConsignmentStatus::Completed
                : $consignment->status,
        ]);

        UpJurusanStockMovement::query()->create([
            'up_jurusan_consignment_id' => $consignment->id,
            'product_id' => null,
            'up_jurusan_pos_sale_id' => $sale?->id,
            'user_id' => $picket->id,
            'type' => 'out',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'gross_amount' => $grossAmount,
            'commission_amount' => $commissionAmount,
            'seller_amount' => $grossAmount - $commissionAmount,
        ]);

        return $grossAmount;
    }

    private function recordProductSale(User $picket, Product $product, int $quantity, ?UpJurusanPosSale $sale = null): int
    {
        if ($quantity > $product->stock) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah keluar tidak boleh melebihi stok produk tersedia.',
            ]);
        }

        $grossAmount = $product->price * $quantity;

        $product->update([
            'stock' => $product->stock - $quantity,
        ]);

        UpJurusanStockMovement::query()->create([
            'up_jurusan_consignment_id' => null,
            'product_id' => $product->id,
            'up_jurusan_pos_sale_id' => $sale?->id,
            'user_id' => $picket->id,
            'type' => 'out',
            'quantity' => $quantity,
            'unit_price' => $product->price,
            'gross_amount' => $grossAmount,
            'commission_amount' => $grossAmount,
            'seller_amount' => 0,
        ]);

        return $grossAmount;
    }

    private function posSaleCode(): string
    {
        return TransactionCode::make();
    }

    /**
     * @param  Collection<int, UpJurusanPosSale>  $sales
     * @return array<int, array{id: int, code: string, receipt_url: string, sold_at: string|null, total_quantity: int, total_amount: int, commission_amount: int, seller_amount: int, products: array<int, array{product_name: string, source: string, quantity: int, unit_price: int, subtotal: int}>}>
     */
    private function dailyReportTransactions(Collection $sales): array
    {
        return $sales
            ->map(function (UpJurusanPosSale $sale) {
                return [
                    'id' => $sale->id,
                    'code' => $sale->code,
                    'receipt_url' => route('picket.pos.receipt', $sale, absolute: false),
                    'sold_at' => $sale->created_at?->toIso8601String(),
                    'total_quantity' => $sale->total_quantity,
                    'total_amount' => $sale->total_amount,
                    'commission_amount' => (int) $sale->movements->sum('commission_amount'),
                    'seller_amount' => (int) $sale->movements->sum('seller_amount'),
                    'products' => $sale->movements
                        ->map(function (UpJurusanStockMovement $movement) {
                            $product = $movement->up_jurusan_consignment_id === null
                                ? $movement->product
                                : $movement->consignment->product;

                            return [
                                'product_name' => $product->name,
                                'source' => $movement->up_jurusan_consignment_id === null ? 'Produk UP' : 'Titipan Seller',
                                'quantity' => $movement->quantity,
                                'unit_price' => $movement->unit_price,
                                'subtotal' => $movement->gross_amount,
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, UpJurusanDailyReportTransaction>  $transactions
     * @return array<int, array{id: int, code: string, receipt_url: string, sold_at: string|null, total_quantity: int, total_amount: int, commission_amount: int, seller_amount: int, products: array<int, array{product_name: string, source: string, quantity: int, unit_price: int, subtotal: int}>}>
     */
    private function dailyReportTransactionSnapshots(Collection $transactions): array
    {
        return $transactions
            ->sortByDesc('sold_at')
            ->map(fn (UpJurusanDailyReportTransaction $transaction) => [
                'id' => $transaction->id,
                'code' => $transaction->code,
                'receipt_url' => $transaction->up_jurusan_pos_sale_id === null
                    ? '#'
                    : route('picket.pos.receipt', $transaction->up_jurusan_pos_sale_id, absolute: false),
                'sold_at' => $transaction->sold_at?->toIso8601String(),
                'total_quantity' => $transaction->total_quantity,
                'total_amount' => $transaction->total_amount,
                'commission_amount' => $transaction->commission_amount,
                'seller_amount' => $transaction->seller_amount,
                'products' => $transaction->items
                    ->map(fn ($item) => [
                        'product_name' => $item->product_name,
                        'source' => $item->source,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'subtotal' => $item->subtotal,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, UpJurusanPosSale>  $sales
     */
    private function snapshotReportTransactions(UpJurusanDailyReport $report, Collection $sales): void
    {
        $sales->each(function (UpJurusanPosSale $sale) use ($report) {
            $transaction = $report->transactions()->create([
                'up_jurusan_pos_sale_id' => $sale->id,
                'code' => $sale->code,
                'total_quantity' => $sale->total_quantity,
                'total_amount' => $sale->total_amount,
                'commission_amount' => (int) $sale->movements->sum('commission_amount'),
                'seller_amount' => (int) $sale->movements->sum('seller_amount'),
                'sold_at' => $sale->created_at,
            ]);

            $sale->movements->each(function (UpJurusanStockMovement $movement) use ($transaction) {
                $product = $movement->up_jurusan_consignment_id === null
                    ? $movement->product
                    : $movement->consignment->product;

                $transaction->items()->create([
                    'up_jurusan_stock_movement_id' => $movement->id,
                    'product_name' => $product->name,
                    'source' => $movement->up_jurusan_consignment_id === null ? 'Produk UP' : 'Titipan Seller',
                    'quantity' => $movement->quantity,
                    'unit_price' => $movement->unit_price,
                    'subtotal' => $movement->gross_amount,
                ]);
            });
        });
    }
}
