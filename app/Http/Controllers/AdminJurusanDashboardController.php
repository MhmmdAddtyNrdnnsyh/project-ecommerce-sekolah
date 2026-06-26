<?php

namespace App\Http\Controllers;

use App\Enums\UpJurusanConsignmentStatus;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminJurusanDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $adminJurusan */
        $adminJurusan = $request->user();

        $consignments = UpJurusanConsignment::query()
            ->with(['seller:id,name', 'product:id,name', 'upJurusan:id,name,admin_jurusan_id'])
            ->whereHas('upJurusan', fn ($query) => $query->where('admin_jurusan_id', $adminJurusan->id));

        return Inertia::render('admin-jurusan/dashboard', [
            'dashboard' => [
                'total_up_jurusans' => UpJurusan::query()
                    ->where('admin_jurusan_id', $adminJurusan->id)
                    ->count(),
                'pending_requests' => (clone $consignments)
                    ->where('status', UpJurusanConsignmentStatus::PendingApproval)
                    ->count(),
                'approved_requests' => (clone $consignments)
                    ->whereIn('status', [
                        UpJurusanConsignmentStatus::Approved,
                        UpJurusanConsignmentStatus::Received,
                        UpJurusanConsignmentStatus::Completed,
                    ])
                    ->count(),
                'active_stock' => (clone $consignments)
                    ->get(['received_quantity', 'sold_quantity'])
                    ->sum(fn (UpJurusanConsignment $consignment) => $consignment->received_quantity - $consignment->sold_quantity),
                'recent_requests' => (clone $consignments)
                    ->latest()
                    ->limit(5)
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
            ],
        ]);
    }
}
