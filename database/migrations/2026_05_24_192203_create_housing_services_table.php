<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housing_services', function (Blueprint $table) {
            $table->unsignedBigInteger('housing_profile_id');
            $table->unsignedBigInteger('basic_service_id');
            $table->boolean('is_available')->default(true);
            $table->primary(['housing_profile_id', 'basic_service_id']);
            $table->foreign('housing_profile_id')->references('id')->on('housing_profiles')->restrictOnDelete();
            $table->foreign('basic_service_id')->references('id')->on('basic_services')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housing_services');
    }
};
