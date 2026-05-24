<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socioeconomic_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->unique();
            $table->unsignedBigInteger('income_range_id')->nullable();
            $table->unsignedBigInteger('income_source_id')->nullable();
            $table->smallInteger('household_earners')->nullable();
            $table->boolean('receives_remittances')->nullable();
            $table->unsignedBigInteger('remittance_country_id')->nullable();
            $table->boolean('student_works')->nullable();
            $table->unsignedBigInteger('employment_type_id')->nullable();
            $table->smallInteger('weekly_work_hours')->nullable();
            $table->boolean('has_scholarship')->nullable();
            $table->string('scholarship_name', 150)->nullable();
            $table->boolean('has_institutional_benefit')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->date('study_date');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('income_range_id')->references('id')->on('income_ranges')->restrictOnDelete();
            $table->foreign('income_source_id')->references('id')->on('income_sources')->restrictOnDelete();
            $table->foreign('remittance_country_id')->references('id')->on('countries')->restrictOnDelete();
            $table->foreign('employment_type_id')->references('id')->on('employment_types')->restrictOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socioeconomic_profiles');
    }
};
