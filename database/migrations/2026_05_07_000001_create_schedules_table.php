<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->restrictOnDelete();
            $table->foreignId('professor_id')->constrained()->restrictOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->string('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('type');
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE schedules ADD CONSTRAINT schedules_times_check CHECK (end_time > start_time)');
        DB::statement("ALTER TABLE schedules ADD CONSTRAINT schedules_start_min_check CHECK (start_time >= '07:00:00')");
        DB::statement("ALTER TABLE schedules ADD CONSTRAINT schedules_end_max_check CHECK (end_time <= '18:00:00')");
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
