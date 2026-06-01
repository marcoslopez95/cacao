<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_primary_dial', 10)->nullable()->after('phone_primary');
            $table->string('phone_secondary_dial', 10)->nullable()->after('phone_secondary');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone_primary_dial', 'phone_secondary_dial']);
        });
    }
};
