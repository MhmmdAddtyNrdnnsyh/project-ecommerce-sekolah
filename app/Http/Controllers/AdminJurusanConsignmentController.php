<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Enums\UpJurusanConsignmentStatus;
use App\Models\UpJurusanConsignment;
use App\Models\UpJurusanPayout;
use App\Models\UpJurusanStockMovement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminJurusanConsignmentController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $adminJurusan */
        $adminJurusan = $request->user();

        return Inertia::render('admin-jurusan/consignments/index', [
            'consignments' => UpJurusanConsignment::query()
                ->with(['seller:id,name', 'product:id,name', 'upJurusan:id,name,admin_jurusan_id'])
                ->whereHas('upJurusan', fn ($query) => $query->where('admin_jurusan_id', $adminJurusan->id))
                ->latest()
                ->get()
                ->map(fn (UpJurusanConsignment $consignment) => [
                    'id' => $consignment->id,
                    'seller_name' => $consignment->seller->name,
                    'product_name' => $consignment->product->name,
                    'up_jurusan_name' => $consignment->upJurusan->name,
                    'requested_quantity' => $consignment->requested_quantity,
                    'status' => [
                        'code' => $consignment->status->value,
                        'label' => $consignment->status->label(),
                    ],
                ])
                ->all(),
        ]);
    }

    public function show(Request $request, UpJurusanConsignment $consignment): Response
    {
        $this->authorizeAdminJurusan($request, $consignment);

        $consignment->load([
            'seller:id,name,email',
            'product:id,name,description,price,stock',
            'upJurusan:id,name,admin_jurusan_id',
        ]);
        $sellerEarnings = (int) UpJurusanStockMovement::query()
            ->where('up_jurusan_consignment_id', $consignment->id)
            ->where('type', 'out')
            ->sum('seller_amount');
        $paidAmount = (int) UpJurusanPayout::query()
            ->where('up_jurusan_consignment_id', $consignment->id)
            ->sum('amount');

        return Inertia::render('admin-jurusan/consignments/show', [
            'consignment' => [
                'id' => $consignment->id,
                'seller' => [
                    'id' => $consignment->seller->id,
                    'name' => $consignment->seller->name,
                    'email' => $consignment->seller->email,
                ],
                'product' => [
                    'id' => $consignment->product->id,
                    'name' => $consignment->product->name,
                    'description' => $consignment->product->description,
                    'price' => $consignment->product->price,
                    'stock' => $consignment->product->stock,
                ],
                'up_jurusan' => [
                    'id' => $consignment->upJurusan->id,
                    'name' => $consignment->upJurusan->name,
                ],
                'requested_quantity' => $consignment->requested_quantity,
                'received_quantity' => $consignment->received_quantity,
                'sold_quantity' => $consignment->sold_quantity,
                'commission_rate' => $consignment->commission_rate,
                'seller_earnings' => $sellerEarnings,
                'paid_amount' => $paidAmount,
                'unpaid_amount' => max(0, $sellerEarnings - $paidAmount),
                'status' => [
                    'code' => $consignment->status->value,
                    'label' => $consignment->status->label(),
                ],
                'created_at' => $consignment->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function approve(Request $request, UpJurusanConsignment $consignment): RedirectResponse
    {
        $this->authorizeAdminJurusan($request, $consignment);

        DB::transaction(function () use ($consignment) {
            $consignment->update(['status' => UpJurusanConsignmentStatus::Approved]);
            $consignment->product()->update([
                'status' => ProductStatus::Approved,
                'rejection_reason' => null,
            ]);
        });

        return to_route('admin-jurusan.consignments.index')
            ->with('success', 'Request titip barang disetujui.');
    }

    public function reject(Request $request, UpJurusanConsignment $consignment): RedirectResponse
    {
        $this->authorizeAdminJurusan($request, $consignment);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($consignment, $validated) {
            $consignment->update([
                'status' => UpJurusanConsignmentStatus::Rejected,
                'note' => $validated['rejection_reason'],
            ]);
            $consignment->product()->update([
                'status' => ProductStatus::Rejected,
                'rejection_reason' => $validated['rejection_reason'],
            ]);
        });

        return to_route('admin-jurusan.consignments.index')
            ->with('success', 'Request titip barang ditolak.');
    }

    public function payout(Request $request, UpJurusanConsignment $consignment): RedirectResponse
    {
        /** @var User $adminJurusan */
        $adminJurusan = $request->user();
        $this->authorizeAdminJurusan($request, $consignment);
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $sellerEarnings = (int) UpJurusanStockMovement::query()
            ->where('up_jurusan_consignment_id', $consignment->id)
            ->where('type', 'out')
            ->sum('seller_amount');
        $paidAmount = (int) UpJurusanPayout::query()
            ->where('up_jurusan_consignment_id', $consignment->id)
            ->sum('amount');
        $unpaidAmount = $sellerEarnings - $paidAmount;

        if ((int) $validated['amount'] > $unpaidAmount) {
            throw ValidationException::withMessages([
                'amount' => 'Jumlah pencairan melebihi saldo seller.',
            ]);
        }

        UpJurusanPayout::query()->create([
            'up_jurusan_consignment_id' => $consignment->id,
            'seller_id' => $consignment->seller_id,
            'user_id' => $adminJurusan->id,
            'amount' => (int) $validated['amount'],
            'note' => $validated['note'] ?? null,
        ]);

        return to_route('admin-jurusan.consignments.show', $consignment)
            ->with('success', 'Pencairan seller berhasil dicatat.');
    }

    private function authorizeAdminJurusan(Request $request, UpJurusanConsignment $consignment): void
    {
        /** @var User $adminJurusan */
        $adminJurusan = $request->user();
        $consignment->load('upJurusan:id,admin_jurusan_id');

        abort_unless($consignment->upJurusan->admin_jurusan_id === $adminJurusan->id, 403);
    }
}
