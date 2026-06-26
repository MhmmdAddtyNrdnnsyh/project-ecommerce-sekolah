<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('up_jurusan_id')
                ->nullable()
                ->after('seller_id')
                ->constrained('up_jurusans')
                ->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('seller_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('up_jurusan_id');
        });
    }
};
