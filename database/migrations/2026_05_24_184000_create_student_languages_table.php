<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_languages', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('language_id');
            $table->unsignedBigInteger('language_level_id');
            $table->boolean('is_mother_tongue')->default(false);
            $table->primary(['student_id', 'language_id']);
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('language_id')->references('id')->on('languages')->restrictOnDelete();
            $table->foreign('language_level_id')->references('id')->on('language_levels')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_languages');
    }
};
