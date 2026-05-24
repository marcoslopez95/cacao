<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained()->restrictOnDelete();
            $table->string('code', 10);
            $table->string('name', 150);
            $table->boolean('active')->default(true);

            $table->unique(['state_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipalities');
    }
};
