<?php

use App\Enums\UpJurusanConsignmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('up_jurusans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_jurusan_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('up_jurusan_consignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('up_jurusan_id')->constrained('up_jurusans')->cascadeOnDelete();
            $table->unsignedInteger('requested_quantity');
            $table->unsignedInteger('received_quantity')->default(0);
            $table->unsignedInteger('sold_quantity')->default(0);
            $table->unsignedTinyInteger('commission_rate')->default(0);
            $table->enum('status', UpJurusanConsignmentStatus::values())->default(UpJurusanConsignmentStatus::PendingApproval->value)->index();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('up_jurusan_consignments');
        Schema::dropIfExists('up_jurusans');
    }
};
