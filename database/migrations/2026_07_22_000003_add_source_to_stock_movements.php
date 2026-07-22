<?php

use App\Enums\StockMovementSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('up_jurusan_stock_movements', function (Blueprint $table) {
            $table->string('source', 32)->nullable()->after('type')->index();
        });

        DB::table('up_jurusan_stock_movements')
            ->whereNotNull('reverses_movement_id')
            ->update(['source' => StockMovementSource::Reverse->value]);

        DB::table('up_jurusan_stock_movements')
            ->whereNull('source')
            ->whereNotNull('up_jurusan_pos_sale_id')
            ->update(['source' => StockMovementSource::PosSale->value]);

        DB::table('up_jurusan_stock_movements')
            ->whereNull('source')
            ->whereNotNull('order_id')
            ->update(['source' => StockMovementSource::OnlineOrder->value]);
    }

    public function down(): void
    {
        Schema::table('up_jurusan_stock_movements', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
