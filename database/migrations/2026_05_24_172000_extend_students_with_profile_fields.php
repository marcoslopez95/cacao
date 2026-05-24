<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('student_code', 20)->unique()->nullable();
            $table->unsignedBigInteger('academic_status_id')->nullable();
            $table->unsignedBigInteger('modality_id')->nullable();
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->unsignedBigInteger('admission_type_id')->nullable();
            $table->decimal('cumulative_gpa', 4, 2)->nullable();
            $table->unsignedBigInteger('grade_id')->nullable();
            $table->date('enrollment_date')->nullable();

            $table->foreign('academic_status_id')->references('id')->on('academic_statuses')->restrictOnDelete();
            $table->foreign('modality_id')->references('id')->on('study_modalities')->restrictOnDelete();
            $table->foreign('shift_id')->references('id')->on('academic_shifts')->restrictOnDelete();
            $table->foreign('admission_type_id')->references('id')->on('admission_types')->restrictOnDelete();
            $table->foreign('grade_id')->references('id')->on('school_grades')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE students ADD CONSTRAINT students_gpa_check CHECK (cumulative_gpa BETWEEN 0.00 AND 20.00)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE students DROP CONSTRAINT IF EXISTS students_gpa_check');

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['academic_status_id']);
            $table->dropForeign(['modality_id']);
            $table->dropForeign(['shift_id']);
            $table->dropForeign(['admission_type_id']);
            $table->dropForeign(['grade_id']);

            $table->dropColumn([
                'student_code',
                'academic_status_id',
                'modality_id',
                'shift_id',
                'admission_type_id',
                'cumulative_gpa',
                'grade_id',
                'enrollment_date',
            ]);
        });
    }
};
