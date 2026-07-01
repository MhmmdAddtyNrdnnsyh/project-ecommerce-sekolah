<?php

use App\Enums\OrderItemStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE order_items MODIFY COLUMN status ENUM('".implode("','", OrderItemStatus::values())."') NOT NULL DEFAULT '".OrderItemStatus::Pending->value."'");
        }
    }

    public function down(): void
    {
        $oldValues = ['pending', 'packed', 'sent'];

        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE order_items MODIFY COLUMN status ENUM('".implode("','", $oldValues)."') NOT NULL DEFAULT '".OrderItemStatus::Pending->value."'");
        }
    }
};
