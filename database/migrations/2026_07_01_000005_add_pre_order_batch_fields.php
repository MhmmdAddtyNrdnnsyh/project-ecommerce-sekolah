<?php

use App\Enums\OrderItemStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->date('pre_order_deadline')->nullable()->after('pre_order_estimate_days');
            $table->unsignedInteger('pre_order_min_quantity')->nullable()->after('pre_order_deadline');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('is_pre_order')->default(false)->after('status')->index();
            $table->unsignedSmallInteger('pre_order_estimate_days')->nullable()->after('is_pre_order');
            $table->date('pre_order_deadline')->nullable()->after('pre_order_estimate_days');
            $table->unsignedInteger('pre_order_min_quantity')->nullable()->after('pre_order_deadline');
            $table->string('pre_order_note')->nullable()->after('pre_order_min_quantity');
        });

        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE order_items MODIFY COLUMN status ENUM('".implode("','", OrderItemStatus::values())."') NOT NULL DEFAULT '".OrderItemStatus::Pending->value."'");
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'pre_order_note',
                'pre_order_min_quantity',
                'pre_order_deadline',
                'pre_order_estimate_days',
                'is_pre_order',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'pre_order_min_quantity',
                'pre_order_deadline',
            ]);
        });

        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $oldValues = ['pending', 'packed', 'sent', 'completed'];
            DB::statement("ALTER TABLE order_items MODIFY COLUMN status ENUM('".implode("','", $oldValues)."') NOT NULL DEFAULT '".OrderItemStatus::Pending->value."'");
        }
    }
};
