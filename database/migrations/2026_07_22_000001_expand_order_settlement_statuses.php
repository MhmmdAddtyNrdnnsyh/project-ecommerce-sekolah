<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $values = [
            ...OrderStatus::values(),
            OrderStatus::LEGACY_PENDING,
        ];

        $this->expandEnumColumn('orders', 'status', $values, OrderStatus::Open->value);

        DB::table('orders')
            ->where('status', OrderStatus::LEGACY_PENDING)
            ->update(['status' => OrderStatus::Open->value]);

        $this->expandEnumColumn('orders', 'status', OrderStatus::values(), OrderStatus::Open->value);
    }

    public function down(): void
    {
        DB::table('orders')
            ->whereIn('status', [
                OrderStatus::Open->value,
                OrderStatus::PartiallyPaid->value,
                OrderStatus::Paid->value,
            ])
            ->update(['status' => OrderStatus::LEGACY_PENDING]);

        $this->expandEnumColumn(
            'orders',
            'status',
            [
                OrderStatus::LEGACY_PENDING,
                OrderStatus::PartiallyCompleted->value,
                OrderStatus::Completed->value,
                OrderStatus::Cancelled->value,
            ],
            OrderStatus::LEGACY_PENDING,
        );
    }

    /**
     * @param  array<int, string>  $values
     */
    private function expandEnumColumn(string $table, string $column, array $values, string $default): void
    {
        $driver = Schema::getConnection()->getDriverName();
        $quotedValues = collect($values)
            ->unique()
            ->map(fn (string $value) => "'".str_replace("'", "''", $value)."'")
            ->implode(', ');

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE {$table} MODIFY {$column} ENUM({$quotedValues}) NOT NULL DEFAULT '{$default}'");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE VARCHAR(255)");
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} SET DEFAULT '{$default}'");

            return;
        }
    }
};
