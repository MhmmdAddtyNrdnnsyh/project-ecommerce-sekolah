<?php

use App\Enums\UpJurusanStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('up_jurusans', function (Blueprint $table) {
            $table->string('status')
                ->default(UpJurusanStatus::Active->value)
                ->after('description');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('up_jurusans', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
