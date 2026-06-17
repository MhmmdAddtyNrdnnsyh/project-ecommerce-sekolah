<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('major_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('majors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('major_group_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->unsignedTinyInteger('grade_min');
            $table->unsignedTinyInteger('grade_max');
            $table->timestamps();

            $table->unique(['code', 'grade_min', 'grade_max']);
        });

        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('major_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('grade_level');
            $table->unsignedSmallInteger('section');
            $table->string('name');
            $table->timestamps();

            $table->unique(['major_id', 'grade_level', 'section']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('class_id');
            $table->dropConstrainedForeignId('position_id');
        });

        Schema::dropIfExists('classes');
        Schema::dropIfExists('majors');
        Schema::dropIfExists('major_groups');
        Schema::dropIfExists('positions');
    }
};
