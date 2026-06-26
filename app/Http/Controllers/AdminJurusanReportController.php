<?php

namespace App\Http\Controllers;

use App\Models\UpJurusanStockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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

        $query = UpJurusanStockMovement::query()
            ->with([
                'user:id,name',
                'consignment.product:id,name',
                'consignment.upJurusan:id,name,admin_jurusan_id',
            ])
            ->whereDate('created_at', $date)
            ->whereHas('consignment.upJurusan', fn ($query) => $query->where('admin_jurusan_id', $adminJurusan->id));

        $movements = $query
            ->latest()
            ->get();

        return Inertia::render('admin-jurusan/reports/index', [
            'filters' => ['date' => $date],
            'summary' => [
                'in' => $movements->where('type', 'in')->sum('quantity'),
                'out' => $movements->where('type', 'out')->sum('quantity'),
                'gross_amount' => $movements->where('type', 'out')->sum('gross_amount'),
                'commission_amount' => $movements->where('type', 'out')->sum('commission_amount'),
                'seller_amount' => $movements->where('type', 'out')->sum('seller_amount'),
            ],
            'movements' => $movements
                ->map(fn (UpJurusanStockMovement $movement) => [
                    'id' => $movement->id,
                    'type' => $movement->type,
                    'quantity' => $movement->quantity,
                    'gross_amount' => $movement->gross_amount,
                    'commission_amount' => $movement->commission_amount,
                    'seller_amount' => $movement->seller_amount,
                    'picket_name' => $movement->user->name,
                    'product_name' => $movement->consignment->product->name,
                    'up_jurusan_name' => $movement->consignment->upJurusan->name,
                    'created_at' => $movement->created_at?->toIso8601String(),
                ])
                ->all(),
        ]);
    }
}
