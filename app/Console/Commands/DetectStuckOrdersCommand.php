<?php

namespace App\Console\Commands;

use App\Support\OrderLivenessService;
use Illuminate\Console\Command;

class DetectStuckOrdersCommand extends Command
{
    protected $signature = 'orders:detect-stuck';

    protected $description = 'Detect expired unpaid and stuck fulfillment/sent orders for admin action';

    public function handle(): int
    {
        $marked = OrderLivenessService::detectAndMarkStuck();
        $this->info("Stuck/expired orders marked: {$marked}");

        return self::SUCCESS;
    }
}
