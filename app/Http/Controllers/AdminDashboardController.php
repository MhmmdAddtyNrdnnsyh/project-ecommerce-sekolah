<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
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
                'roleDistributionData' => $this->roleDistributionData(),
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
        $pendingSellerVerification = User::query()
            ->where('role', UserRole::Seller->value)
            ->whereNull('email_verified_at')
            ->count();

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
                'context' => $this->number($pendingSellerVerification).' seller belum verifikasi email',
                'tone' => 'emerald',
                'icon' => 'store',
            ],
            [
                'label' => 'Produk Live',
                'value' => '0',
                'context' => 'Modul produk belum tersedia',
                'tone' => 'amber',
                'icon' => 'packageCheck',
            ],
            [
                'label' => 'Kasus Prioritas',
                'value' => $this->number($pendingSellerVerification),
                'context' => 'Akun seller perlu ditinjau',
                'tone' => 'rose',
                'icon' => 'alertTriangle',
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
     * @return array<int, array{ticket: string, area: string, owner: string, priority: string, status: string, sla: string, icon: string}>
     */
    private function adminQueue(): array
    {
        return User::query()
            ->where('role', UserRole::Seller->value)
            ->whereNull('email_verified_at')
            ->latest()
            ->limit(4)
            ->get(['id', 'name', 'created_at'])
            ->map(fn (User $seller) => [
                'ticket' => 'USR-'.$seller->id,
                'area' => 'Verifikasi email seller',
                'owner' => $seller->name,
                'priority' => 'High',
                'status' => 'Open',
                'sla' => $seller->created_at?->diffForHumans() ?? '-',
                'icon' => 'userRoundCheck',
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label: string, value: string, progress: int, tone: string}>
     */
    private function platformHealth(): array
    {
        $totalUsers = User::query()->count();
        $verifiedUsers = User::query()->whereNotNull('email_verified_at')->count();
        $sellerCount = $this->roleCount(UserRole::Seller);
        $verifiedSellers = User::query()
            ->where('role', UserRole::Seller->value)
            ->whereNotNull('email_verified_at')
            ->count();
        $usersWithPosition = User::query()->whereNotNull('position_id')->count();
        $pendingVerification = User::query()->whereNull('email_verified_at')->count();

        return [
            [
                'label' => 'Email user terverifikasi',
                'value' => $this->percentLabel($verifiedUsers, $totalUsers),
                'progress' => $this->percent($verifiedUsers, $totalUsers),
                'tone' => 'emerald',
            ],
            [
                'label' => 'Seller terverifikasi',
                'value' => $this->percentLabel($verifiedSellers, $sellerCount),
                'progress' => $this->percent($verifiedSellers, $sellerCount),
                'tone' => 'blue',
            ],
            [
                'label' => 'Profil posisi lengkap',
                'value' => $this->percentLabel($usersWithPosition, $totalUsers),
                'progress' => $this->percent($usersWithPosition, $totalUsers),
                'tone' => 'emerald',
            ],
            [
                'label' => 'Akun belum verifikasi email',
                'value' => $this->number($pendingVerification).' akun',
                'progress' => $this->percent($pendingVerification, max($totalUsers, 1)),
                'tone' => $pendingVerification > 0 ? 'rose' : 'emerald',
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
            UserRole::Seller => 'store',
            UserRole::PicketOfficer => 'clipboardCheck',
            UserRole::Buyer => 'badgeCheck',
        };
    }

    private function activityTone(UserRole $role): string
    {
        return match ($role) {
            UserRole::Admin => 'blue',
            UserRole::Seller => 'emerald',
            UserRole::PicketOfficer => 'amber',
            UserRole::Buyer => 'blue',
        };
    }
}
