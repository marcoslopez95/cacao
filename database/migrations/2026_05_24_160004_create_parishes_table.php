<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained()->restrictOnDelete();
            $table->string('code', 10);
            $table->string('name', 150);
            $table->boolean('active')->default(true);

            $table->unique(['municipality_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parishes');
    }
};
