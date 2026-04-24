<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coordinations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // career | grade | academic
            $table->string('education_level'); // university | secondary
            $table->string('secondary_type')->nullable(); // media_general | bachillerato (only when type=grade)
            // career_id references careers table (not yet created — FK added in Academic module migration)
            $table->unsignedBigInteger('career_id')->nullable()->index();
            $table->unsignedTinyInteger('grade_year')->nullable(); // 1-6, only when type=grade
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coordinations');
    }
};
