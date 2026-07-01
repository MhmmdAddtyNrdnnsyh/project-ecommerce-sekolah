<?php

use App\Support\TransactionCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'code')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('code')->nullable()->after('id')->unique();
            });
        }

        DB::table('orders')
            ->whereNull('code')
            ->orderBy('id')
            ->get(['id'])
            ->each(fn (object $order) => DB::table('orders')
                ->where('id', $order->id)
                ->update(['code' => TransactionCode::make()]));
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'code')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropUnique(['code']);
                $table->dropColumn('code');
            });
        }
    }
};
