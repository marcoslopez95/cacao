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
        Schema::create('grade_configs', function (Blueprint $table) {
            $table->id();
            $table->string('level');
            $table->foreignId('period_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('scale_type');
            $table->decimal('scale_min', 8, 2)->nullable();
            $table->decimal('scale_max', 8, 2)->nullable();
            $table->decimal('passing_value', 8, 2);
            $table->timestamps();

            $table->unique(['level', 'period_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_configs');
    }
};
