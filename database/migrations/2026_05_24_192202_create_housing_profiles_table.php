<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housing_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('students')->restrictOnDelete();
            $table->foreignId('housing_type_id')->nullable()->constrained('housing_types')->restrictOnDelete();
            $table->foreignId('tenure_type_id')->nullable()->constrained('tenure_types')->restrictOnDelete();
            $table->foreignId('construction_material_id')->nullable()->constrained('construction_materials')->restrictOnDelete();
            $table->unsignedSmallInteger('room_count')->nullable();
            $table->unsignedSmallInteger('bathroom_count')->nullable();
            $table->unsignedSmallInteger('household_members')->nullable();
            $table->foreignId('commute_time_id')->nullable()->constrained('commute_times')->restrictOnDelete();
            $table->foreignId('transport_type_id')->nullable()->constrained('transport_types')->restrictOnDelete();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE housing_profiles ADD COLUMN is_overcrowded boolean GENERATED ALWAYS AS ((household_members::numeric / NULLIF(room_count, 0)) > 2.5) STORED');
    }

    public function down(): void
    {
        Schema::dropIfExists('housing_profiles');
    }
};
