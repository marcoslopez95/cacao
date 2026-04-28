<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE pensums DROP CONSTRAINT IF EXISTS pensums_period_type_check');
            DB::statement("ALTER TABLE pensums ADD CONSTRAINT pensums_period_type_check CHECK (period_type IN ('semester', 'year', 'trimester'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE pensums DROP CONSTRAINT IF EXISTS pensums_period_type_check');
            DB::statement("ALTER TABLE pensums ADD CONSTRAINT pensums_period_type_check CHECK (period_type IN ('semester', 'year'))");
        }
    }
};
