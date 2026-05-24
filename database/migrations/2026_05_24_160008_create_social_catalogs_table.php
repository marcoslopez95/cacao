<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kinship_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('marital_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('institution_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('transfer_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('digital_levels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('language_levels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('living_arrangements', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('household_head_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('religions', function (Blueprint $table) {
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
        Schema::dropIfExists('religions');
        Schema::dropIfExists('household_head_types');
        Schema::dropIfExists('living_arrangements');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('language_levels');
        Schema::dropIfExists('digital_levels');
        Schema::dropIfExists('transfer_reasons');
        Schema::dropIfExists('institution_types');
        Schema::dropIfExists('marital_statuses');
        Schema::dropIfExists('kinship_types');
    }
};
