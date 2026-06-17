<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SellerDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $seller */
        $seller = $request->user();

        return Inertia::render('seller/dashboard', [
            'dashboard' => [
                'stats' => $this->stats($seller),
                'salesData' => $this->salesData(),
                'orderMixData' => $this->orderMixData(),
                'orders' => [],
                'topProducts' => [],
                'stockAlerts' => [],
                'tasks' => $this->tasks($seller),
            ],
        ]);
    }

    /**
     * @return array<int, array{label: string, value: string, context: string, trend: string, tone: string, icon: string}>
     */
    private function stats(User $seller): array
    {
        return [
            [
                'label' => 'Omzet Bulan Ini',
                'value' => 'Rp 0',
                'context' => 'Modul transaksi belum tersedia',
                'trend' => '0%',
                'tone' => 'blue',
                'icon' => 'badgeDollarSign',
            ],
            [
                'label' => 'Pesanan Masuk',
                'value' => '0',
                'context' => 'Belum ada tabel pesanan',
                'trend' => '0',
                'tone' => 'emerald',
                'icon' => 'shoppingCart',
            ],
            [
                'label' => 'Produk Aktif',
                'value' => '0',
                'context' => 'Belum ada produk terdaftar',
                'trend' => '0 item',
                'tone' => 'amber',
                'icon' => 'package',
            ],
            [
                'label' => 'Status Akun',
                'value' => $seller->hasVerifiedEmail() ? 'Aktif' : 'Perlu verifikasi',
                'context' => 'Bergabung '.$seller->created_at?->diffForHumans(),
                'trend' => $seller->hasVerifiedEmail() ? 'Live' : 'Review',
                'tone' => $seller->hasVerifiedEmail() ? 'emerald' : 'rose',
                'icon' => 'wallet',
            ],
        ];
    }

    /**
     * @return array<int, array{day: string, sales: int, orders: int}>
     */
    private function salesData(): array
    {
        return collect(range(6, 0))
            ->map(fn (int $daysAgo) => [
                'day' => now()->subDays($daysAgo)->translatedFormat('D'),
                'sales' => 0,
                'orders' => 0,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{status: string, label: string, value: int, fill: string}>
     */
    private function orderMixData(): array
    {
        return [
            [
                'status' => 'paid',
                'label' => 'Dibayar',
                'value' => 0,
                'fill' => 'var(--color-paid)',
            ],
            [
                'status' => 'packed',
                'label' => 'Dikemas',
                'value' => 0,
                'fill' => 'var(--color-packed)',
            ],
            [
                'status' => 'sent',
                'label' => 'Dikirim',
                'value' => 0,
                'fill' => 'var(--color-sent)',
            ],
            [
                'status' => 'issue',
                'label' => 'Kendala',
                'value' => 0,
                'fill' => 'var(--color-issue)',
            ],
        ];
    }

    /**
     * @return array<int, array{title: string, detail: string, action: string, icon: string, tone: string}>
     */
    private function tasks(User $seller): array
    {
        return [
            [
                'title' => 'Siapkan katalog produk pertama',
                'detail' => 'Modul produk belum tersedia; data dashboard akan otomatis terisi saat modul aktif.',
                'action' => 'Menunggu modul produk',
                'icon' => 'package',
                'tone' => 'amber',
            ],
            [
                'title' => 'Akun seller sudah siap digunakan',
                'detail' => 'Masuk sebagai '.$seller->name.' dan pantau data toko dari halaman ini.',
                'action' => 'Lihat profil',
                'icon' => 'store',
                'tone' => 'blue',
            ],
            [
                'title' => 'Aktifkan operasional transaksi',
                'detail' => 'Tambahkan tabel order, produk, stok, dan payout untuk mengisi metrik real-time.',
                'action' => 'Butuh modul lanjutan',
                'icon' => 'megaphone',
                'tone' => 'emerald',
            ],
        ];
    }
}
