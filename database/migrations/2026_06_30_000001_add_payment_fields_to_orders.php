<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_status', PaymentStatus::values())
                ->default(PaymentStatus::Unpaid->value)
                ->after('status')
                ->index();
            $table->enum('payment_method', PaymentMethod::values())
                ->default(PaymentMethod::Cash->value)
                ->after('payment_status');
            $table->string('payment_proof_path')->nullable()->after('payment_method');
            $table->timestamp('payment_confirmed_at')->nullable()->after('payment_proof_path');
            $table->foreignId('payment_confirmed_by')
                ->nullable()
                ->after('payment_confirmed_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('payment_rejection_reason')->nullable()->after('payment_confirmed_by');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['payment_confirmed_by']);
            $table->dropColumn([
                'payment_status',
                'payment_method',
                'payment_proof_path',
                'payment_confirmed_at',
                'payment_confirmed_by',
                'payment_rejection_reason',
            ]);
        });
    }
};
