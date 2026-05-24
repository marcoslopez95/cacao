<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('country_id')->constrained('countries')->restrictOnDelete();
            $table->foreignId('state_id')->nullable()->constrained('states')->restrictOnDelete();
            $table->foreignId('municipality_id')->nullable()->constrained('municipalities')->restrictOnDelete();
            $table->foreignId('parish_id')->nullable()->constrained('parishes')->restrictOnDelete();
            $table->foreignId('geographic_zone_id')->nullable()->constrained('geographic_zones')->restrictOnDelete();
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
