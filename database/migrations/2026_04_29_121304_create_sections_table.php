<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('university');
            $table->foreignId('period_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->string('code', 10);
            $table->foreignId('theory_classroom_id')->nullable()->constrained('classrooms')->restrictOnDelete();
            $table->foreignId('lab_classroom_id')->nullable()->constrained('classrooms')->restrictOnDelete();
            $table->unsignedSmallInteger('capacity');
            $table->timestamps();

            $table->unique(['period_id', 'subject_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
