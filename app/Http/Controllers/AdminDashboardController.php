<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerApplication;
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
                'orderTrendData' => $this->orderTrendData(),
                'adminQueue' => $this->adminQueue(),
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
                'label' => 'Seller Terdaftar',
                'value' => $this->number($sellerCount),
                'context' => $this->number($sellerCount).' seller terdaftar',
                'tone' => 'emerald',
                'icon' => 'store',
            ],
            [
                'label' => 'Produk Aktif',
                'value' => $this->number($approvedProducts),
                'context' => $this->number($totalProducts).' total produk',
                'tone' => 'amber',
                'icon' => 'packageCheck',
            ],
            [
                'label' => 'Order Online',
                'value' => $this->number($totalOrders),
                'context' => 'Rp '.$this->number($totalOrderValue).' nilai order online tercatat',
                'tone' => 'rose',
                'icon' => 'walletCards',
            ],
        ];
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
     * @return array<int, array{key: string, type: string, title: string, owner: string, status: string, age: string, href: string}>
     */
    private function adminQueue(): array
    {
        $pendingProducts = Product::query()
            ->with('seller:id,name')
            ->where('status', ProductStatus::Pending)
            ->whereNotNull('seller_id')
            ->oldest()
            ->limit(6)
            ->get(['id', 'seller_id', 'name', 'created_at'])
            ->map(fn (Product $product) => [
                'key' => 'product-'.$product->id,
                'type' => 'Moderasi Produk',
                'title' => $product->name,
                'owner' => $product->seller->name,
                'status' => 'Menunggu',
                'age' => $product->created_at?->diffForHumans() ?? '-',
                'href' => route('admin.products.moderation.index', absolute: false),
                'created_at' => $product->created_at->timestamp,
            ]);

        $pendingApplications = SellerApplication::query()
            ->with('user:id,name')
            ->where('status', SellerApplication::PENDING)
            ->oldest()
            ->limit(6)
            ->get(['id', 'user_id', 'store_name', 'created_at'])
            ->map(fn (SellerApplication $application) => [
                'key' => 'seller-application-'.$application->id,
                'type' => 'Pengajuan Seller',
                'title' => $application->store_name,
                'owner' => $application->user->name,
                'status' => 'Menunggu',
                'age' => $application->created_at?->diffForHumans() ?? '-',
                'href' => route('admin.seller-applications.index', absolute: false),
                'created_at' => $application->created_at->timestamp,
            ]);

        return $pendingProducts
            ->concat($pendingApplications)
            ->sortBy('created_at')
            ->take(6)
            ->map(function (array $item) {
                unset($item['created_at']);

                return $item;
            })
            ->values()
            ->all();
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
}
