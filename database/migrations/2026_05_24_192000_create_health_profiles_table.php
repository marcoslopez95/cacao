<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->foreignId('blood_type_id')->nullable()->constrained('blood_types')->restrictOnDelete();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('height_cm', 5, 2)->nullable();
            $table->boolean('has_disability')->nullable();
            $table->foreignId('disability_type_id')->nullable()->constrained('disability_types')->restrictOnDelete();
            $table->text('disability_description')->nullable();
            $table->boolean('has_special_needs')->nullable();
            $table->text('special_needs_description')->nullable();
            $table->text('chronic_condition')->nullable();
            $table->text('regular_medication')->nullable();
            $table->text('allergies')->nullable();
            $table->boolean('has_medical_insurance')->nullable();
            $table->foreignId('insurance_type_id')->nullable()->constrained('insurance_types')->restrictOnDelete();
            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->string('emergency_contact_relation', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_profiles');
    }
};
