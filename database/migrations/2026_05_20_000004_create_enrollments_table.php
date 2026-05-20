<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('period_id')->constrained()->restrictOnDelete();
            $table->foreignId('pensum_id')->constrained()->restrictOnDelete();
            $table->integer('uc_disponibles')->default(0);
            $table->integer('uc_inscritas')->default(0);
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->index('student_id');
            $table->index('period_id');
            $table->unique(['student_id', 'period_id'], 'unique_student_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
