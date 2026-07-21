<?php

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->expandEnumColumn('orders', 'status', OrderStatus::values(), OrderStatus::Pending->value);
        $this->expandEnumColumn('order_items', 'status', OrderItemStatus::values(), OrderItemStatus::Pending->value);
    }

    public function down(): void
    {
        $this->expandEnumColumn('orders', 'status', [OrderStatus::Pending->value], OrderStatus::Pending->value);
        $this->expandEnumColumn(
            'order_items',
            'status',
            [
                OrderItemStatus::Pending->value,
                OrderItemStatus::InProduction->value,
                OrderItemStatus::Ready->value,
                OrderItemStatus::Packed->value,
                OrderItemStatus::Sent->value,
                OrderItemStatus::Completed->value,
            ],
            OrderItemStatus::Pending->value,
        );
    }

    /**
     * @param  array<int, string>  $values
     */
    private function expandEnumColumn(string $table, string $column, array $values, string $default): void
    {
        $driver = Schema::getConnection()->getDriverName();
        $quotedValues = collect($values)
            ->map(fn (string $value) => "'".str_replace("'", "''", $value)."'")
            ->implode(', ');

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE {$table} MODIFY {$column} ENUM({$quotedValues}) NOT NULL DEFAULT '{$default}'");

            return;
        }

        if ($driver === 'pgsql') {
            // Original migrations may have used varchar; ensure column accepts new values.
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE VARCHAR(255)");
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} SET DEFAULT '{$default}'");

            return;
        }

        // sqlite and others: enum columns are typically stored as varchar already.
    }
};
