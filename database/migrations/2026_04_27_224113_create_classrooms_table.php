<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->restrictOnDelete();
            $table->string('identifier', 50);
            $table->enum('type', ['theory', 'laboratory']);
            $table->unsignedSmallInteger('capacity');
            $table->timestamps();

            $table->unique(['building_id', 'identifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
