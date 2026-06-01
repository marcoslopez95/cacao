<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('sections')->restrictOnDelete();
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('linked_session_id')->nullable()->constrained('class_sessions')->restrictOnDelete();
            $table->string('type')->default('regular');
            $table->string('status')->default('scheduled');
            $table->boolean('professor_present')->default(true);
            $table->string('topic', 255)->nullable();
            $table->date('held_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
