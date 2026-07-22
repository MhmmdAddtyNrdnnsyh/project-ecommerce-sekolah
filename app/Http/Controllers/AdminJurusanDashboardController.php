<?php

namespace App\Http\Controllers;

use App\Enums\UpJurusanConsignmentStatus;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\UpJurusanDailyReport;
use App\Models\User;
use App\Support\ReportAggregationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminJurusanDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $adminJurusan */
        $adminJurusan = $request->user();

        $upJurusan = UpJurusan::query()
            ->with('picketOfficers:id,name,up_jurusan_id')
            ->where('admin_jurusan_id', $adminJurusan->id)
            ->first(['id', 'name', 'admin_jurusan_id']);
        $consignments = UpJurusanConsignment::query()
            ->whereHas('upJurusan', fn ($query) => $query->where('admin_jurusan_id', $adminJurusan->id));
        $pendingRequests = (clone $consignments)
            ->where('status', UpJurusanConsignmentStatus::PendingApproval)
            ->count();
        $awaitingReceive = (clone $consignments)
            ->where('status', UpJurusanConsignmentStatus::Approved)
            ->whereColumn('received_quantity', '<', 'requested_quantity')
            ->count();
        $pendingRows = (clone $consignments)
            ->with(['seller:id,name', 'product:id,name', 'upJurusan:id,name'])
            ->where('status', UpJurusanConsignmentStatus::PendingApproval)
            ->oldest()
            ->limit(5)
            ->get();
        $resolvedRows = $pendingRows->count() < 5
            ? (clone $consignments)
                ->with(['seller:id,name', 'product:id,name', 'upJurusan:id,name'])
                ->where('status', '!=', UpJurusanConsignmentStatus::PendingApproval)
                ->latest()
                ->limit(5 - $pendingRows->count())
                ->get()
            : collect();

        return Inertia::render('admin-jurusan/dashboard', [
            'dashboard' => [
                'today_sales' => $upJurusan === null
                    ? 0
                    : ReportAggregationService::upTodaySales((int) $upJurusan->id),
                'pending_requests' => $pendingRequests,
                'awaiting_receive' => $awaitingReceive,
                'report_status' => $this->reportStatus($upJurusan),
                'recent_requests' => $pendingRows
                    ->concat($resolvedRows)
                    ->map(fn (UpJurusanConsignment $consignment) => [
                        'id' => $consignment->id,
                        'seller_name' => $consignment->seller->name,
                        'product_name' => $consignment->product->name,
                        'up_jurusan_name' => $consignment->upJurusan->name,
                        'requested_quantity' => $consignment->requested_quantity,
                        'href' => route('admin-jurusan.consignments.show', $consignment, absolute: false),
                        'status' => [
                            'code' => $consignment->status->value,
                            'label' => $consignment->status->label(),
                        ],
                    ])
                    ->all(),
            ],
        ]);
    }

    /**
     * @return array{code: string, label: string, picket_name: string|null, submitted_at: string|null}
     */
    private function reportStatus(?UpJurusan $upJurusan): array
    {
        $picket = $upJurusan?->picketOfficers->first();

        if ($picket === null) {
            return [
                'code' => 'no_picket',
                'label' => 'Belum ada picket',
                'picket_name' => null,
                'submitted_at' => null,
            ];
        }

        $report = UpJurusanDailyReport::query()
            ->where('up_jurusan_id', $upJurusan->id)
            ->where('user_id', $picket->id)
            ->whereDate('report_date', now()->toDateString())
            ->whereNotNull('submitted_at')
            ->first(['submitted_at']);

        return [
            'code' => $report === null ? 'not_submitted' : 'submitted',
            'label' => $report === null ? 'Belum dikirim' : 'Sudah dikirim',
            'picket_name' => $picket->name,
            'submitted_at' => $report?->submitted_at?->toIso8601String(),
        ];
    }
}
