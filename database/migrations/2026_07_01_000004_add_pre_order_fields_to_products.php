<?php

use App\Enums\ProductFulfillmentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('fulfillment_type', ProductFulfillmentType::values())
                ->default(ProductFulfillmentType::ReadyStock->value)
                ->after('sales_method')
                ->index();
            $table->unsignedSmallInteger('pre_order_estimate_days')
                ->nullable()
                ->after('fulfillment_type');
            $table->string('pre_order_note')
                ->nullable()
                ->after('pre_order_estimate_days');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'pre_order_note',
                'pre_order_estimate_days',
                'fulfillment_type',
            ]);
        });
    }
};
