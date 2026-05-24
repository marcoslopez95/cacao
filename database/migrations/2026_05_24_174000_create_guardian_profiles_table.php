<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardian_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guardian_id')->unique()->constrained('guardians')->restrictOnDelete();
            $table->string('occupation', 150)->nullable();
            $table->string('employer', 200)->nullable();
            $table->string('work_phone', 20)->nullable();
            $table->foreignId('education_level_id')->nullable()->constrained('education_levels')->restrictOnDelete();
            $table->foreignId('marital_status_id')->nullable()->constrained('marital_statuses')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_profiles');
    }
};
