<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('up_jurusan_daily_report_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('up_jurusan_daily_report_id');
            $table->foreignId('up_jurusan_pos_sale_id')->nullable();
            $table->string('code');
            $table->unsignedInteger('total_quantity');
            $table->unsignedBigInteger('total_amount');
            $table->unsignedBigInteger('commission_amount')->default(0);
            $table->unsignedBigInteger('seller_amount')->default(0);
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();

            $table->unique(['up_jurusan_daily_report_id', 'up_jurusan_pos_sale_id'], 'up_report_pos_sale_unique');
            $table->foreign('up_jurusan_daily_report_id', 'up_report_tx_report_fk')
                ->references('id')
                ->on('up_jurusan_daily_reports')
                ->cascadeOnDelete();
            $table->foreign('up_jurusan_pos_sale_id', 'up_report_tx_pos_sale_fk')
                ->references('id')
                ->on('up_jurusan_pos_sales')
                ->nullOnDelete();
        });

        Schema::create('up_jurusan_daily_report_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('up_jurusan_daily_report_transaction_id');
            $table->foreignId('up_jurusan_stock_movement_id')->nullable();
            $table->string('product_name');
            $table->string('source');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();

            $table->foreign('up_jurusan_daily_report_transaction_id', 'up_report_item_tx_fk')
                ->references('id')
                ->on('up_jurusan_daily_report_transactions')
                ->cascadeOnDelete();
            $table->foreign('up_jurusan_stock_movement_id', 'up_report_item_movement_fk')
                ->references('id')
                ->on('up_jurusan_stock_movements')
                ->nullOnDelete();
        });

        $this->backfillExistingReports();
    }

    public function down(): void
    {
        Schema::dropIfExists('up_jurusan_daily_report_transaction_items');
        Schema::dropIfExists('up_jurusan_daily_report_transactions');
    }

    private function backfillExistingReports(): void
    {
        DB::table('up_jurusan_daily_reports')
            ->orderBy('id')
            ->get()
            ->each(function (object $report): void {
                DB::table('up_jurusan_pos_sales')
                    ->where('up_jurusan_id', $report->up_jurusan_id)
                    ->where('user_id', $report->user_id)
                    ->whereDate('created_at', $report->report_date)
                    ->orderBy('id')
                    ->get()
                    ->each(function (object $sale) use ($report): void {
                        $transactionId = DB::table('up_jurusan_daily_report_transactions')->insertGetId([
                            'up_jurusan_daily_report_id' => $report->id,
                            'up_jurusan_pos_sale_id' => $sale->id,
                            'code' => $sale->code,
                            'total_quantity' => $sale->total_quantity,
                            'total_amount' => $sale->total_amount,
                            'commission_amount' => DB::table('up_jurusan_stock_movements')
                                ->where('up_jurusan_pos_sale_id', $sale->id)
                                ->sum('commission_amount'),
                            'seller_amount' => DB::table('up_jurusan_stock_movements')
                                ->where('up_jurusan_pos_sale_id', $sale->id)
                                ->sum('seller_amount'),
                            'sold_at' => $sale->created_at,
                            'created_at' => $report->created_at,
                            'updated_at' => $report->updated_at,
                        ]);

                        DB::table('up_jurusan_stock_movements')
                            ->where('up_jurusan_pos_sale_id', $sale->id)
                            ->orderBy('id')
                            ->get()
                            ->each(function (object $movement) use ($transactionId, $report): void {
                                DB::table('up_jurusan_daily_report_transaction_items')->insert([
                                    'up_jurusan_daily_report_transaction_id' => $transactionId,
                                    'up_jurusan_stock_movement_id' => $movement->id,
                                    'product_name' => $this->movementProductName(
                                        $movement->product_id,
                                        $movement->up_jurusan_consignment_id,
                                    ),
                                    'source' => $movement->up_jurusan_consignment_id === null ? 'Produk UP' : 'Titipan Seller',
                                    'quantity' => $movement->quantity,
                                    'unit_price' => $movement->unit_price,
                                    'subtotal' => $movement->gross_amount,
                                    'created_at' => $report->created_at,
                                    'updated_at' => $report->updated_at,
                                ]);
                            });
                    });
            });
    }

    private function movementProductName(?int $productId, ?int $consignmentId): string
    {
        if ($productId !== null) {
            return (string) DB::table('products')
                ->where('id', $productId)
                ->value('name');
        }

        $productId = DB::table('up_jurusan_consignments')
            ->where('id', $consignmentId)
            ->value('product_id');

        return (string) DB::table('products')
            ->where('id', $productId)
            ->value('name');
    }
};
