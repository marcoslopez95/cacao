<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('careers', function (Blueprint $table) {
            $table->string('name', 255)->after('career_category_id');
            $table->string('code', 10)->unique()->after('name');
            $table->boolean('active')->default(true)->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('careers', function (Blueprint $table) {
            $table->dropColumn(['name', 'code', 'active']);
        });
    }
};
