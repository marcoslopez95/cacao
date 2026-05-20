<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('section_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->index('enrollment_id');
            $table->index('section_id');
            $table->unique(['enrollment_id', 'subject_id'], 'unique_enrollment_subject');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_details');
    }
};
