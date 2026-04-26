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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pensum_id')->constrained()->restrictOnDelete();
            $table->string('name', 255);
            $table->string('code', 20);
            $table->tinyInteger('credits_uc')->unsigned();
            $table->tinyInteger('period_number')->unsigned();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['pensum_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
