<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function forceCancel(User $user, Order $order): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function forceComplete(User $user, Order $order): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function markManualReview(User $user, Order $order): bool
    {
        return $user->role === UserRole::Admin;
    }
}
