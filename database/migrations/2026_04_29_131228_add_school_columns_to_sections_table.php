<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropUnique(['period_id', 'subject_id', 'code']);
            $table->foreignId('subject_id')->nullable()->change();
            $table->string('code', 10)->nullable()->change();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "CREATE UNIQUE INDEX sections_university_unique ON sections (period_id, subject_id, code) WHERE type = 'university'"
            );
        }

        Schema::table('sections', function (Blueprint $table) {
            $table->foreignId('pensum_id')->nullable()->after('period_id')->constrained()->restrictOnDelete();
            $table->tinyInteger('grade')->unsigned()->nullable()->after('pensum_id');
            $table->string('letter', 5)->nullable()->after('grade');
            $table->foreignId('main_teacher_id')->nullable()->after('letter')->constrained('professors')->restrictOnDelete();
            $table->foreignId('classroom_id')->nullable()->after('main_teacher_id')->constrained('classrooms')->restrictOnDelete();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "CREATE UNIQUE INDEX sections_school_unique ON sections (period_id, pensum_id, grade, letter) WHERE type = 'school'"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS sections_university_unique');
            DB::statement('DROP INDEX IF EXISTS sections_school_unique');
        }

        Schema::table('sections', function (Blueprint $table) {
            $table->dropForeign(['classroom_id']);
            $table->dropForeign(['main_teacher_id']);
            $table->dropForeign(['pensum_id']);
            $table->dropColumn(['classroom_id', 'main_teacher_id', 'letter', 'grade', 'pensum_id']);
            $table->foreignId('subject_id')->nullable(false)->change();
            $table->string('code', 10)->nullable(false)->change();
            $table->unique(['period_id', 'subject_id', 'code']);
        });
    }
};
