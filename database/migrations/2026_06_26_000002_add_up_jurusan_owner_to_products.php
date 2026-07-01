<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OWNER_CONSTRAINT = 'products_owner_xor_chk';

    public function up(): void
    {
        if (! Schema::hasColumn('products', 'up_jurusan_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('up_jurusan_id')
                    ->nullable()
                    ->after('seller_id')
                    ->constrained('up_jurusans')
                    ->nullOnDelete();
            });
        }

        DB::table('products')
            ->whereNotNull('seller_id')
            ->whereNotNull('up_jurusan_id')
            ->update(['up_jurusan_id' => null]);

        $this->addOwnerConstraint();
    }

    public function down(): void
    {
        $this->dropOwnerConstraint();

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('up_jurusan_id');
        });
    }

    private function addOwnerConstraint(): void
    {
        $expression = '((seller_id IS NOT NULL AND up_jurusan_id IS NULL) OR (seller_id IS NULL AND up_jurusan_id IS NOT NULL))';

        match (DB::getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE products ADD CONSTRAINT '.self::OWNER_CONSTRAINT.' CHECK '.$expression),
            default => null,
        };
    }

    private function dropOwnerConstraint(): void
    {
        match (DB::getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE products DROP CONSTRAINT '.self::OWNER_CONSTRAINT),
            default => null,
        };
    }
};
