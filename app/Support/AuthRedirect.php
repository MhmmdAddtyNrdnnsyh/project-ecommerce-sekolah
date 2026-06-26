<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;

class AuthRedirect
{
    public static function path(?User $user): string
    {
        return match ($user?->role) {
            UserRole::Admin => route('dashboard', absolute: false),
            UserRole::AdminJurusan => route('admin-jurusan.dashboard', absolute: false),
            UserRole::Seller => route('seller.dashboard', absolute: false),
            UserRole::PicketOfficer => route('picket.dashboard', absolute: false),
            default => route('home', absolute: false),
        };
    }

    public static function intendedPath(Request $request): string
    {
        $fallback = self::path($request->user());
        $intended = $request->session()->pull('url.intended', $fallback);

        if (! is_string($intended) || $intended === '') {
            return $fallback;
        }

        $path = self::normalizePath($intended, $request);

        if ($path === null || ! self::isAllowedForUser($path, $request->user())) {
            return $fallback;
        }

        return $path;
    }

    private static function normalizePath(string $url, Request $request): ?string
    {
        $parts = parse_url($url);

        if ($parts === false) {
            return null;
        }

        $host = $parts['host'] ?? null;
        $port = $parts['port'] ?? null;

        if (is_string($host)) {
            $intendedHost = strtolower($host.(is_int($port) ? ':'.$port : ''));

            if ($intendedHost !== strtolower($request->getHttpHost())) {
                return null;
            }
        }

        $path = $parts['path'] ?? '/';

        if (! str_starts_with($path, '/')) {
            return null;
        }

        $query = $parts['query'] ?? null;

        return $path.(is_string($query) && $query !== '' ? '?'.$query : '');
    }

    private static function isAllowedForUser(string $path, ?User $user): bool
    {
        $pathOnly = parse_url($path, PHP_URL_PATH) ?: '/';

        return match ($pathOnly) {
            route('dashboard', absolute: false) => $user?->role === UserRole::Admin,
            route('admin-jurusan.dashboard', absolute: false) => $user?->role === UserRole::AdminJurusan,
            route('admin-jurusan.up-jurusan.index', absolute: false) => $user?->role === UserRole::AdminJurusan,
            route('admin-jurusan.consignments.index', absolute: false) => $user?->role === UserRole::AdminJurusan,
            route('admin-jurusan.reports.index', absolute: false) => $user?->role === UserRole::AdminJurusan,
            route('seller.dashboard', absolute: false) => $user?->role === UserRole::Seller,
            route('picket.dashboard', absolute: false) => $user?->role === UserRole::PicketOfficer,
            route('picket.pos', absolute: false) => $user?->role === UserRole::PicketOfficer,
            route('picket.orders', absolute: false) => $user?->role === UserRole::PicketOfficer,
            route('picket.reports', absolute: false) => $user?->role === UserRole::PicketOfficer,
            route('picket.up-jurusan.consignments.index', absolute: false) => $user?->role === UserRole::PicketOfficer,
            default => true,
        };
    }
}
