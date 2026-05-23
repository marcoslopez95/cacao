<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('grade_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_config_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->decimal('weight', 5, 2);
            $table->unsignedTinyInteger('sort_order');
            $table->boolean('is_remedial')->default(false);
            $table->timestamps();

            $table->index('grade_config_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_slots');
    }
};
