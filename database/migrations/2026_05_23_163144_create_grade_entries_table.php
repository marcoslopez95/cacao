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
        Schema::create('grade_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_detail_id')->constrained()->restrictOnDelete();
            $table->foreignId('grade_slot_id')->constrained()->restrictOnDelete();
            $table->foreignId('lapse_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('grade_entries')->restrictOnDelete();
            $table->string('name')->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('value', 8, 2)->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index('enrollment_detail_id');
            $table->index('grade_slot_id');
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_entries');
    }
};
