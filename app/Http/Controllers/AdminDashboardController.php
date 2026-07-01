<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('dashboard', [
            'dashboard' => [
                'stats' => $this->stats(),
                'userGrowthData' => $this->userGrowthData(),
                'orderTrendData' => $this->orderTrendData(),
                'roleDistributionData' => $this->roleDistributionData(),
                'productStatusData' => $this->productStatusData(),
                'adminQueue' => $this->adminQueue(),
                'platformHealth' => $this->platformHealth(),
                'activities' => $this->activities(),
            ],
        ]);
    }

    /**
     * @return array<int, array{label: string, value: string, context: string, tone: string, icon: string}>
     */
    private function stats(): array
    {
        $totalUsers = User::query()->count();
        $usersThisMonth = User::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        $sellerCount = $this->roleCount(UserRole::Seller);
        $totalProducts = Product::query()->count();
        $approvedProducts = Product::query()
            ->where('status', ProductStatus::Approved)
            ->count();
        $trackedOrders = Order::query()
            ->where('payment_status', '!=', PaymentStatus::Rejected->value);
        $totalOrders = (clone $trackedOrders)->count();
        $totalOrderValue = (int) (clone $trackedOrders)->sum('total_price');

        return [
            [
                'label' => 'Pengguna Terdaftar',
                'value' => $this->number($totalUsers),
                'context' => '+'.$this->number($usersThisMonth).' user bulan ini',
                'tone' => 'blue',
                'icon' => 'users',
            ],
            [
                'label' => 'Seller Aktif',
                'value' => $this->number($sellerCount),
                'context' => $this->number($sellerCount).' seller terdaftar',
                'tone' => 'emerald',
                'icon' => 'store',
            ],
            [
                'label' => 'Produk Live',
                'value' => $this->number($approvedProducts),
                'context' => $this->number($totalProducts).' total produk',
                'tone' => 'amber',
                'icon' => 'packageCheck',
            ],
            [
                'label' => 'Transaksi',
                'value' => $this->number($totalOrders),
                'context' => 'Rp '.$this->number($totalOrderValue).' nilai order online gross',
                'tone' => 'rose',
                'icon' => 'walletCards',
            ],
        ];
    }

    /**
     * @return array<int, array{month: string, users: int, sellers: int}>
     */
    private function userGrowthData(): array
    {
        $start = now()->startOfMonth()->subMonths(7);

        return collect(range(0, 7))
            ->map(function (int $offset) use ($start) {
                $month = $start->copy()->addMonths($offset);

                return [
                    'month' => $month->translatedFormat('M'),
                    'users' => User::query()
                        ->whereBetween('created_at', [
                            $month->copy()->startOfMonth(),
                            $month->copy()->endOfMonth(),
                        ])
                        ->count(),
                    'sellers' => User::query()
                        ->where('role', UserRole::Seller->value)
                        ->whereBetween('created_at', [
                            $month->copy()->startOfMonth(),
                            $month->copy()->endOfMonth(),
                        ])
                        ->count(),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{month: string, orders: int, revenue: int}>
     */
    private function orderTrendData(): array
    {
        $start = now()->startOfMonth()->subMonths(7);

        $totals = Order::query()
            ->where('payment_status', '!=', PaymentStatus::Rejected->value)
            ->where('created_at', '>=', $start)
            ->get(['created_at', 'total_price'])
            ->groupBy(fn (Order $order) => $order->created_at?->format('Y-n') ?? '');

        return collect(range(0, 7))
            ->map(function (int $offset) use ($start, $totals) {
                $month = $start->copy()->addMonths($offset);
                $items = $totals->get($month->format('Y-n'), collect());

                return [
                    'month' => $month->translatedFormat('M'),
                    'orders' => $items->count(),
                    'revenue' => (int) $items->sum('total_price'),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{role: string, label: string, value: int, fill: string}>
     */
    private function roleDistributionData(): array
    {
        $totalUsers = max(User::query()->count(), 1);

        return collect(UserRole::cases())
            ->map(fn (UserRole $role) => [
                'role' => $role === UserRole::PicketOfficer ? 'picket' : $role->value,
                'label' => $role->label(),
                'value' => (int) round(($this->roleCount($role) / $totalUsers) * 100),
                'fill' => 'var(--color-'.($role === UserRole::PicketOfficer ? 'picket' : $role->value).')',
            ])
            ->all();
    }

    /**
     * @return array<int, array{status: string, label: string, value: int, fill: string}>
     */
    private function productStatusData(): array
    {
        $counts = Product::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(ProductStatus::cases())
            ->map(fn (ProductStatus $status) => [
                'status' => $status->value,
                'label' => $status->label(),
                'value' => (int) ($counts[$status->value] ?? 0),
                'fill' => $this->productStatusColor($status),
            ])
            ->filter(fn (array $item) => $item['value'] > 0)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{ticket: string, area: string, owner: string, priority: string, status: string, sla: string, icon: string}>
     */
    private function adminQueue(): array
    {
        $pendingProducts = Product::query()
            ->with('seller:id,name')
            ->where('status', ProductStatus::Pending)
            ->whereNotNull('seller_id')
            ->oldest()
            ->limit(4)
            ->get(['id', 'seller_id', 'name', 'created_at'])
            ->map(fn (Product $product) => [
                'ticket' => 'PRD-'.$product->id,
                'area' => 'Moderasi produk',
                'owner' => $product->seller->name,
                'priority' => 'High',
                'status' => 'Open',
                'sla' => $product->created_at?->diffForHumans() ?? '-',
                'icon' => 'packageCheck',
            ]);

        return $pendingProducts->values()->all();
    }

    /**
     * @return array<int, array{label: string, value: string, progress: int, tone: string}>
     */
    private function platformHealth(): array
    {
        $totalUsers = User::query()->count();
        $usersWithPosition = User::query()->whereNotNull('position_id')->count();
        $totalProducts = Product::query()->count();
        $approvedProducts = Product::query()
            ->where('status', ProductStatus::Approved)
            ->count();
        $pendingProducts = Product::query()
            ->where('status', ProductStatus::Pending)
            ->count();
        $totalOrders = Order::query()->count();

        return [
            [
                'label' => 'Profil posisi lengkap',
                'value' => $this->percentLabel($usersWithPosition, $totalUsers),
                'progress' => $this->percent($usersWithPosition, $totalUsers),
                'tone' => 'emerald',
            ],
            [
                'label' => 'Produk approved',
                'value' => $this->percentLabel($approvedProducts, $totalProducts),
                'progress' => $this->percent($approvedProducts, $totalProducts),
                'tone' => 'amber',
            ],
            [
                'label' => 'Produk menunggu moderasi',
                'value' => $this->number($pendingProducts),
                'progress' => $this->percent($pendingProducts, max($totalProducts, 1)),
                'tone' => 'blue',
            ],
            [
                'label' => 'Order tercatat',
                'value' => $this->number($totalOrders),
                'progress' => $totalOrders > 0 ? 100 : 0,
                'tone' => 'emerald',
            ],
        ];
    }

    /**
     * @return array<int, array{title: string, detail: string, time: string, icon: string, tone: string}>
     */
    private function activities(): array
    {
        return User::query()
            ->latest()
            ->limit(4)
            ->get(['id', 'name', 'role', 'created_at'])
            ->map(fn (User $user) => [
                'title' => 'User baru terdaftar',
                'detail' => $user->name.' terdaftar sebagai '.$user->role->label().'.',
                'time' => $user->created_at?->diffForHumans() ?? '-',
                'icon' => $this->activityIcon($user->role),
                'tone' => $this->activityTone($user->role),
            ])
            ->values()
            ->all();
    }

    private function roleCount(UserRole $role): int
    {
        return User::query()->where('role', $role->value)->count();
    }

    private function percent(int $value, int $total): int
    {
        if ($total === 0) {
            return 0;
        }

        return (int) round(($value / $total) * 100);
    }

    private function percentLabel(int $value, int $total): string
    {
        return $this->percent($value, $total).'%';
    }

    private function number(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    private function activityIcon(UserRole $role): string
    {
        return match ($role) {
            UserRole::Admin => 'shieldCheck',
            UserRole::AdminJurusan => 'clipboardCheck',
            UserRole::Seller => 'store',
            UserRole::PicketOfficer => 'clipboardCheck',
            UserRole::Buyer => 'badgeCheck',
        };
    }

    private function activityTone(UserRole $role): string
    {
        return match ($role) {
            UserRole::Admin => 'blue',
            UserRole::AdminJurusan => 'amber',
            UserRole::Seller => 'emerald',
            UserRole::PicketOfficer => 'amber',
            UserRole::Buyer => 'blue',
        };
    }

    private function productStatusColor(ProductStatus $status): string
    {
        return match ($status) {
            ProductStatus::Draft => '#64748b',
            ProductStatus::Pending => '#2563eb',
            ProductStatus::Approved => '#10b981',
            ProductStatus::Rejected => '#e11d48',
        };
    }
}
