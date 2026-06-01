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
        Schema::table('health_profiles', function (Blueprint $table) {
            $table->string('emergency_contact_phone_dial', 10)->nullable()->after('emergency_contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('health_profiles', function (Blueprint $table) {
            $table->dropColumn('emergency_contact_phone_dial');
        });
    }
};
