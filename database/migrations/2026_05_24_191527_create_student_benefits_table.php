<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_benefits', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('benefit_id');
            $table->boolean('is_active')->default(true);
            $table->date('since')->nullable();
            $table->date('until')->nullable();
            $table->primary(['student_id', 'benefit_id']);
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('benefit_id')->references('id')->on('institutional_benefits')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE student_benefits ADD CONSTRAINT student_benefits_dates_check CHECK (until IS NULL OR since IS NULL OR until >= since)');
    }

    public function down(): void
    {
        Schema::dropIfExists('student_benefits');
    }
};
