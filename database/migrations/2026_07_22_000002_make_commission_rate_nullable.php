<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE up_jurusan_consignments MODIFY commission_rate TINYINT UNSIGNED NULL DEFAULT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE up_jurusan_consignments ALTER COLUMN commission_rate DROP NOT NULL');
            DB::statement('ALTER TABLE up_jurusan_consignments ALTER COLUMN commission_rate DROP DEFAULT');
        } elseif ($driver === 'sqlite') {
            // Fresh installs already create nullable column; keep data fix only.
        }

        DB::table('up_jurusan_consignments')
            ->where('status', 'pending_approval')
            ->where('commission_rate', 0)
            ->update(['commission_rate' => null]);
    }

    public function down(): void
    {
        DB::table('up_jurusan_consignments')
            ->whereNull('commission_rate')
            ->update(['commission_rate' => 0]);

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE up_jurusan_consignments MODIFY commission_rate TINYINT UNSIGNED NOT NULL DEFAULT 0');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE up_jurusan_consignments ALTER COLUMN commission_rate SET DEFAULT 0');
            DB::statement('ALTER TABLE up_jurusan_consignments ALTER COLUMN commission_rate SET NOT NULL');
        }
    }
};
