<?php

namespace App\Http\Controllers;

use App\Models\UpJurusan;
use App\Models\UpJurusanDailyReport;
use App\Models\UpJurusanDailyReportTransaction;
use App\Models\User;
use App\Support\ReportAggregationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @phpstan-type ReportItem array{id: int, product_name: string, quantity: int, unit_price: int, subtotal: int}
 * @phpstan-type ReportTransaction array{id: string, code: string, total_quantity: int, total_amount: int, commission_amount: int, seller_amount: int, created_at: string|null, items: array<int, ReportItem>}
 */
class AdminJurusanReportController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $adminJurusan */
        $adminJurusan = $request->user();
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $date = $validated['date'] ?? now()->toDateString();
        $upJurusanIds = UpJurusan::query()
            ->where('admin_jurusan_id', $adminJurusan->id)
            ->pluck('id');
        $dailyReports = UpJurusanDailyReport::query()
            ->with(['user:id,name', 'upJurusan:id,name'])
            ->whereIn('up_jurusan_id', $upJurusanIds)
            ->whereDate('report_date', $date)
            ->latest('submitted_at')
            ->get();

        return Inertia::render('admin-jurusan/reports/index', [
            'filters' => ['date' => $date],
            'summary' => ReportAggregationService::adminDailyReportsSummary($dailyReports),
            'reports' => $dailyReports
                ->map(fn (UpJurusanDailyReport $report) => [
                    'id' => $report->id,
                    'picket_name' => $report->user->name,
                    'up_jurusan_name' => $report->upJurusan->name,
                    'total_sold' => $report->total_sold,
                    'total_revenue' => $report->total_revenue,
                    'submitted_at' => $report->submitted_at->toIso8601String(),
                ])
                ->all(),
        ]);
    }

    public function show(Request $request, UpJurusanDailyReport $report): Response
    {
        /** @var User $adminJurusan */
        $adminJurusan = $request->user();
        $report->load([
            'user:id,name',
            'upJurusan:id,name,admin_jurusan_id',
            'transactions' => fn ($query) => $query->latest('sold_at'),
            'transactions.items',
        ]);

        abort_unless($report->upJurusan->admin_jurusan_id === $adminJurusan->id, 403);

        return Inertia::render('admin-jurusan/reports/show', [
            'report' => [
                'id' => $report->id,
                'date' => $report->report_date->toDateString(),
                'picket_name' => $report->user->name,
                'up_jurusan_name' => $report->upJurusan->name,
                'total_sold' => $report->total_sold,
                'total_revenue' => $report->total_revenue,
                'submitted_at' => $report->submitted_at->toIso8601String(),
            ],
            'transactions' => $this->reportTransactions($report),
        ]);
    }

    /**
     * @return array<int, ReportTransaction>
     */
    private function reportTransactions(UpJurusanDailyReport $report): array
    {
        return $report->transactions
            ->map(fn (UpJurusanDailyReportTransaction $transaction) => [
                'id' => "report-transaction-{$transaction->id}",
                'code' => $transaction->code,
                'total_quantity' => $transaction->total_quantity,
                'total_amount' => $transaction->total_amount,
                'commission_amount' => $transaction->commission_amount,
                'seller_amount' => $transaction->seller_amount,
                'created_at' => $transaction->sold_at?->toIso8601String(),
                'items' => $transaction->items
                    ->map(fn ($item) => [
                        'id' => $item->id,
                        'product_name' => $item->product_name,
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
}
