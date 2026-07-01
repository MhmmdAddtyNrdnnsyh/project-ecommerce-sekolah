<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('up_jurusan_pos_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('up_jurusan_id')->constrained('up_jurusans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->unsignedInteger('total_quantity')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->timestamps();
        });

        Schema::table('up_jurusan_stock_movements', function (Blueprint $table) {
            $table->foreignId('up_jurusan_pos_sale_id')
                ->nullable()
                ->after('product_id')
                ->constrained('up_jurusan_pos_sales')
                ->nullOnDelete();
        });

        Schema::create('up_jurusan_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('up_jurusan_id')->constrained('up_jurusans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('report_date');
            $table->unsignedInteger('total_sold');
            $table->unsignedBigInteger('total_revenue');
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->unique(['up_jurusan_id', 'user_id', 'report_date'], 'up_daily_report_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('up_jurusan_daily_reports');

        Schema::table('up_jurusan_stock_movements', function (Blueprint $table) {
            $table->dropForeign(['up_jurusan_pos_sale_id']);
            $table->dropColumn('up_jurusan_pos_sale_id');
        });

        Schema::dropIfExists('up_jurusan_pos_sales');
    }
};
