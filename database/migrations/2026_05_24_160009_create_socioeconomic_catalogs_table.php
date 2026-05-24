<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_ranges', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('income_sources', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('employment_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('institutional_benefits', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('housing_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('tenure_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('construction_materials', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('basic_services', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('commute_times', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('transport_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_types');
        Schema::dropIfExists('commute_times');
        Schema::dropIfExists('basic_services');
        Schema::dropIfExists('construction_materials');
        Schema::dropIfExists('tenure_types');
        Schema::dropIfExists('housing_types');
        Schema::dropIfExists('institutional_benefits');
        Schema::dropIfExists('employment_types');
        Schema::dropIfExists('income_sources');
        Schema::dropIfExists('income_ranges');
    }
};
