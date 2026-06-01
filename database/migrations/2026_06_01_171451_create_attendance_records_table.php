<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')->constrained('class_sessions')->restrictOnDelete();
            $table->foreignId('enrollment_detail_id')->constrained('enrollment_details')->restrictOnDelete();
            $table->string('status')->default('present');
            $table->timestamps();
            $table->unique(['class_session_id', 'enrollment_detail_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
