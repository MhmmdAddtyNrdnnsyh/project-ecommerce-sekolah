<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('up_jurusan_consignments', function (Blueprint $table) {
            $table->unsignedTinyInteger('commission_rate')->default(0)->after('sold_quantity');
        });

        Schema::table('up_jurusan_stock_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_price')->default(0)->after('quantity');
            $table->unsignedBigInteger('gross_amount')->default(0)->after('unit_price');
            $table->unsignedBigInteger('commission_amount')->default(0)->after('gross_amount');
            $table->unsignedBigInteger('seller_amount')->default(0)->after('commission_amount');
        });

        Schema::create('up_jurusan_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('up_jurusan_consignment_id')->constrained('up_jurusan_consignments')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('up_jurusan_payouts');

        Schema::table('up_jurusan_stock_movements', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'gross_amount', 'commission_amount', 'seller_amount']);
        });

        Schema::table('up_jurusan_consignments', function (Blueprint $table) {
            $table->dropColumn('commission_rate');
        });
    }
};
