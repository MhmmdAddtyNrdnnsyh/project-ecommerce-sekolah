<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('pickup_location');
            $table->timestamp('cancelled_at')->nullable()->after('expires_at');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->string('cancel_reason')->nullable()->after('cancelled_by');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('pre_order_note');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->string('cancel_reason')->nullable()->after('cancelled_by');
            $table->index(['payment_status', 'status']);
        });

        Schema::table('up_jurusan_stock_movements', function (Blueprint $table) {
            $table->foreignId('reverses_movement_id')
                ->nullable()
                ->after('note')
                ->constrained('up_jurusan_stock_movements')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('up_jurusan_stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reverses_movement_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['payment_status', 'status']);
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['cancelled_at', 'cancel_reason']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['expires_at', 'cancelled_at', 'cancel_reason']);
        });
    }
};
