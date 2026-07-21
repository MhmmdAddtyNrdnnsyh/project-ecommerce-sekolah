<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->timestamp('status_changed_at')->nullable()->after('status');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('requires_manual_review')->default(false)->after('cancel_reason');
            $table->timestamp('requires_manual_review_at')->nullable()->after('requires_manual_review');
            $table->string('requires_manual_review_reason')->nullable()->after('requires_manual_review_at');
            $table->timestamp('stuck_detected_at')->nullable()->after('requires_manual_review_reason');
            $table->json('stuck_reasons')->nullable()->after('stuck_detected_at');
        });

        DB::table('order_items')->update([
            'status_changed_at' => DB::raw('updated_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'requires_manual_review',
                'requires_manual_review_at',
                'requires_manual_review_reason',
                'stuck_detected_at',
                'stuck_reasons',
            ]);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('status_changed_at');
        });
    }
};
