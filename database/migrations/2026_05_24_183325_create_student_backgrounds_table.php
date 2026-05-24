<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_backgrounds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('students')->restrictOnDelete();
            $table->string('previous_institution', 200)->nullable();
            $table->foreignId('institution_type_id')->nullable()->constrained('institution_types')->restrictOnDelete();
            $table->smallInteger('graduation_year')->nullable();
            $table->decimal('previous_gpa', 4, 2)->nullable();
            $table->boolean('repeated_grade')->nullable();
            $table->string('repeated_grade_description', 100)->nullable();
            $table->foreignId('transfer_reason_id')->nullable()->constrained('transfer_reasons')->restrictOnDelete();
            $table->boolean('has_prior_studies')->nullable();
            $table->text('prior_studies_description')->nullable();
            $table->foreignId('digital_level_id')->nullable()->constrained('digital_levels')->restrictOnDelete();
            $table->unsignedBigInteger('mother_education_level_id')->nullable();
            $table->unsignedBigInteger('father_education_level_id')->nullable();
            $table->timestamps();

            $table->foreign('mother_education_level_id', 'sb_mother_education_level_fk')
                ->references('id')->on('education_levels')->restrictOnDelete();
            $table->foreign('father_education_level_id', 'sb_father_education_level_fk')
                ->references('id')->on('education_levels')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE student_backgrounds ADD CONSTRAINT student_backgrounds_gpa_check CHECK (previous_gpa BETWEEN 0.00 AND 20.00)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE student_backgrounds DROP CONSTRAINT IF EXISTS student_backgrounds_gpa_check');
        Schema::dropIfExists('student_backgrounds');
    }
};
