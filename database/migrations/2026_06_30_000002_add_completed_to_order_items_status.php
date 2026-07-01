<?php

use App\Enums\OrderItemStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE order_items MODIFY COLUMN status ENUM('".implode("','", OrderItemStatus::values())."') NOT NULL DEFAULT '".OrderItemStatus::Pending->value."'");
        }
    }

    public function down(): void
    {
        $oldValues = ['pending', 'packed', 'sent'];

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE order_items MODIFY COLUMN status ENUM('".implode("','", $oldValues)."') NOT NULL DEFAULT '".OrderItemStatus::Pending->value."'");
        }
    }
};
