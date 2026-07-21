<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\OrderLivenessService;
use Illuminate\Console\Command;

class ExpireUnpaidOrdersCommand extends Command
{
    protected $signature = 'orders:expire-unpaid';

    protected $description = 'Cancel unpaid order items that passed the payment SLA and restock inventory';

    public function handle(): int
    {
        $systemActor = User::query()
            ->where('role', UserRole::Admin)
            ->orderBy('id')
            ->first();

        if ($systemActor === null) {
            $this->warn('No admin user available to attribute expiry cancellations.');

            return self::FAILURE;
        }

        $cancelled = OrderLivenessService::expireUnpaidOrders($systemActor);
        $this->info("Expired unpaid items cancelled: {$cancelled}");

        return self::SUCCESS;
    }
}
