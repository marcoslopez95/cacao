<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->string('name')->after('user_id')->default('');
            $table->string('relation')->after('name')->default(''); // madre, padre, tutor, etc.
        });
    }

    public function down(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->dropColumn(['name', 'relation']);
        });
    }
};
