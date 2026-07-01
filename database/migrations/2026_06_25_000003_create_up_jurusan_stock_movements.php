<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('up_jurusan_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('up_jurusan_consignment_id')->nullable()->constrained('up_jurusan_consignments')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['in', 'out'])->index();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price')->default(0);
            $table->unsignedBigInteger('gross_amount')->default(0);
            $table->unsignedBigInteger('commission_amount')->default(0);
            $table->unsignedBigInteger('seller_amount')->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('up_jurusan_stock_movements');
    }
};
