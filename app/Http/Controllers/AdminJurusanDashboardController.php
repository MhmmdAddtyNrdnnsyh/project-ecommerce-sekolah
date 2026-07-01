<?php

namespace App\Http\Controllers;

use App\Enums\UpJurusanConsignmentStatus;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\UpJurusanDailyReport;
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
        $consignmentRows = (clone $consignments)->latest()->get();
        $activeStock = $consignmentRows
            ->sum(fn (UpJurusanConsignment $consignment) => $consignment->received_quantity - $consignment->sold_quantity);

        return Inertia::render('admin-jurusan/dashboard', [
            'dashboard' => [
                'total_up_jurusans' => UpJurusan::query()
                    ->where('admin_jurusan_id', $adminJurusan->id)
                    ->count(),
                'pending_requests' => $consignmentRows
                    ->where('status', UpJurusanConsignmentStatus::PendingApproval)
                    ->count(),
                'approved_requests' => $consignmentRows
                    ->whereIn('status', [
                        UpJurusanConsignmentStatus::Approved,
                        UpJurusanConsignmentStatus::Received,
                        UpJurusanConsignmentStatus::Completed,
                    ])
                    ->count(),
                'active_stock' => $activeStock,
                'sales_trend_data' => $this->salesTrendData($adminJurusan),
                'status_distribution' => collect(UpJurusanConsignmentStatus::cases())
                    ->map(fn (UpJurusanConsignmentStatus $status) => [
                        'status' => $status->value,
                        'label' => $status->label(),
                        'value' => $consignmentRows->where('status', $status)->count(),
                        'fill' => $this->statusColor($status),
                    ])
                    ->filter(fn (array $item) => $item['value'] > 0)
                    ->values()
                    ->all(),
                'stock_distribution' => $consignmentRows
                    ->map(fn (UpJurusanConsignment $consignment) => [
                        'product' => $consignment->product->name,
                        'seller' => $consignment->seller->name,
                        'stock' => max($consignment->received_quantity - $consignment->sold_quantity, 0),
                        'sold' => $consignment->sold_quantity,
                    ])
                    ->filter(fn (array $item) => $item['stock'] > 0 || $item['sold'] > 0)
                    ->sortByDesc(fn (array $item) => $item['stock'] + $item['sold'])
                    ->take(6)
                    ->values()
                    ->all(),
                'recent_requests' => $consignmentRows
                    ->take(5)
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

    /**
     * @return array<int, array{day: string, reports: int, sold: int, revenue: int}>
     */
    private function salesTrendData(User $adminJurusan): array
    {
        $start = now()->subDays(6)->startOfDay();

        $reports = UpJurusanDailyReport::query()
            ->where('report_date', '>=', $start->toDateString())
            ->whereHas('upJurusan', fn ($query) => $query->where('admin_jurusan_id', $adminJurusan->id))
            ->get(['report_date', 'total_sold', 'total_revenue'])
            ->groupBy(fn (UpJurusanDailyReport $report) => $report->report_date->toDateString());

        return collect(range(6, 0))
            ->map(function (int $daysAgo) use ($reports) {
                $date = now()->subDays($daysAgo);
                $items = $reports->get($date->toDateString(), collect());

                return [
                    'day' => $date->translatedFormat('D'),
                    'reports' => $items->count(),
                    'sold' => (int) $items->sum('total_sold'),
                    'revenue' => (int) $items->sum('total_revenue'),
                ];
            })
            ->values()
            ->all();
    }

    private function statusColor(UpJurusanConsignmentStatus $status): string
    {
        return match ($status) {
            UpJurusanConsignmentStatus::PendingApproval => '#2563eb',
            UpJurusanConsignmentStatus::Approved => '#f59e0b',
            UpJurusanConsignmentStatus::Received => '#10b981',
            UpJurusanConsignmentStatus::Completed => '#0f766e',
            UpJurusanConsignmentStatus::Rejected => '#e11d48',
            UpJurusanConsignmentStatus::Cancelled => '#64748b',
        };
    }
}
