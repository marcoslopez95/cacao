<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lapses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('number');
            $table->string('name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->unique(['period_id', 'number']);
        });

        DB::statement('ALTER TABLE lapses ADD CONSTRAINT lapses_dates_check CHECK (end_date > start_date)');
    }

    public function down(): void
    {
        Schema::dropIfExists('lapses');
    }
};
