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
        Schema::create('grade_letter_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_config_id')->constrained()->restrictOnDelete();
            $table->string('letter', 2);
            $table->decimal('numeric_equiv', 8, 2);
            $table->boolean('is_passing')->default(false);
            $table->unsignedTinyInteger('sort_order');
            $table->timestamps();

            $table->unique(['grade_config_id', 'letter']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_letter_values');
    }
};
