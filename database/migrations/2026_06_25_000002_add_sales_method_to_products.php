<?php

use App\Enums\ProductSalesMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('sales_method', ProductSalesMethod::values())
                ->default(ProductSalesMethod::SelfManaged->value)
                ->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('sales_method');
        });
    }
};
