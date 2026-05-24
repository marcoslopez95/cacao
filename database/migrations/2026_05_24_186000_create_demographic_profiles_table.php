<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demographic_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('birth_city', 100)->nullable();
            $table->unsignedBigInteger('birth_state_id')->nullable();
            $table->unsignedBigInteger('birth_country_id')->nullable();
            $table->boolean('is_indigenous')->nullable();
            $table->string('indigenous_community', 100)->nullable();
            $table->unsignedBigInteger('native_language_id')->nullable();
            $table->boolean('is_returned_migrant')->nullable();
            $table->unsignedBigInteger('previous_country_id')->nullable();
            $table->unsignedBigInteger('religion_id')->nullable();
            $table->boolean('practices_sport')->nullable();
            $table->string('sport', 100)->nullable();
            $table->text('cultural_activities')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('birth_state_id')->references('id')->on('states')->restrictOnDelete();
            $table->foreign('birth_country_id', 'demographic_profiles_birth_country_fk')->references('id')->on('countries')->restrictOnDelete();
            $table->foreign('native_language_id')->references('id')->on('languages')->restrictOnDelete();
            $table->foreign('previous_country_id', 'demographic_profiles_previous_country_fk')->references('id')->on('countries')->restrictOnDelete();
            $table->foreign('religion_id')->references('id')->on('religions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demographic_profiles');
    }
};
