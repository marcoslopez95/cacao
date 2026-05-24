<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_guardians', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('guardian_id');
            $table->unsignedBigInteger('kinship_type_id');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_emergency_contact')->default(false);
            $table->primary(['student_id', 'guardian_id']);
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('guardian_id')->references('id')->on('guardians')->restrictOnDelete();
            $table->foreign('kinship_type_id')->references('id')->on('kinship_types')->restrictOnDelete();
        });

        $otherId = DB::table('kinship_types')->where('code', 'other')->value('id');

        if ($otherId !== null) {
            DB::table('students')
                ->whereNotNull('guardian_id')
                ->orderBy('id')
                ->select('id as student_id', 'guardian_id')
                ->lazy()
                ->each(fn ($row) => DB::table('student_guardians')->insert([
                    'student_id' => $row->student_id,
                    'guardian_id' => $row->guardian_id,
                    'kinship_type_id' => $otherId,
                    'is_primary' => true,
                    'is_emergency_contact' => true,
                ]));
        }

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['guardian_id']);
            $table->dropColumn('guardian_id');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('guardian_id')->nullable();
            $table->foreign('guardian_id')->references('id')->on('guardians')->restrictOnDelete();
        });

        DB::statement('UPDATE students s SET guardian_id = sg.guardian_id FROM student_guardians sg WHERE sg.student_id = s.id AND sg.is_primary = true');

        Schema::dropIfExists('student_guardians');
    }
};
