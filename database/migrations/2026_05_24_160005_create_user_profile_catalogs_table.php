<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('geographic_zones', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('attachment_document_types', function (Blueprint $table) {
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
        Schema::dropIfExists('attachment_document_types');
        Schema::dropIfExists('geographic_zones');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('genders');
    }
};
