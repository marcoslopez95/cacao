<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('professor_id')->unique()->constrained('professors')->restrictOnDelete();
            $table->string('employee_code', 20)->unique();
            $table->string('academic_title', 100)->nullable();
            $table->string('specialty', 150)->nullable();
            $table->foreignId('contract_type_id')->constrained('contract_types')->restrictOnDelete();
            $table->foreignId('dedication_type_id')->constrained('dedication_types')->restrictOnDelete();
            $table->smallInteger('weekly_hour_load')->nullable();
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->foreignId('employment_status_id')->constrained('employment_statuses')->restrictOnDelete();
            $table->boolean('is_coordinator')->default(false);
            $table->unsignedBigInteger('coordinated_department_id')->nullable();
            $table->date('coordinator_since')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE staff_profiles ADD CONSTRAINT staff_profiles_termination_check CHECK (termination_date IS NULL OR termination_date >= hire_date)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE staff_profiles DROP CONSTRAINT IF EXISTS staff_profiles_termination_check');
        Schema::dropIfExists('staff_profiles');
    }
};
