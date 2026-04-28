<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20)->unique();
            $table->enum('type', ['semester', 'year', 'trimester']);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['upcoming', 'active', 'closed'])->default('upcoming');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE periods ADD CONSTRAINT periods_dates_check CHECK (end_date > start_date)');
    }

    public function down(): void
    {
        Schema::dropIfExists('periods');
    }
};
