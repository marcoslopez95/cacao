<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->unique();
            $table->unsignedBigInteger('guardian_marital_status_id')->nullable();
            $table->smallInteger('children_count')->nullable();
            $table->smallInteger('sibling_position')->nullable();
            $table->smallInteger('sibling_count')->nullable();
            $table->unsignedBigInteger('living_arrangement_id')->nullable();
            $table->unsignedBigInteger('household_head_type_id')->nullable();
            $table->string('household_head_name', 150)->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('guardian_marital_status_id')->references('id')->on('marital_statuses')->restrictOnDelete();
            $table->foreign('living_arrangement_id')->references('id')->on('living_arrangements')->restrictOnDelete();
            $table->foreign('household_head_type_id')->references('id')->on('household_head_types')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_profiles');
    }
};
