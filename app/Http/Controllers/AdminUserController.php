<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = User::query()->withCount(['products', 'orders']);

        if ($search = $validated['q'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $validated['role'] ?? null) {
            $query->where('role', $role);
        }

        $users = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('admin/users/index', [
            'users' => $users->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => [
                    'code' => $user->role->value,
                    'label' => $user->role->label(),
                ],
                'products_count' => $user->products_count,
                'orders_count' => $user->orders_count,
                'created_at' => $user->created_at?->toIso8601String(),
            ]),
            'roles' => UserRole::options(),
            'filters' => [
                'q' => $validated['q'] ?? '',
                'role' => $validated['role'] ?? '',
            ],
        ]);
    }
}
