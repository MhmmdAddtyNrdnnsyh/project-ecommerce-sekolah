<?php

use App\Enums\OrderItemStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->enum('status', OrderItemStatus::values())
                ->default(OrderItemStatus::Pending->value)
                ->index()
                ->after('subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
