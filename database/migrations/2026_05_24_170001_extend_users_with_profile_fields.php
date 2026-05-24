<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable()->after('id');
            $table->string('last_name', 100)->nullable()->after('first_name');
        });

        DB::statement("UPDATE users SET first_name = split_part(name, ' ', 1), last_name = NULLIF(TRIM(SUBSTRING(name FROM POSITION(' ' IN name))), '')");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        DB::statement("ALTER TABLE users ADD COLUMN name varchar(255) GENERATED ALWAYS AS (COALESCE(first_name, '') || ' ' || COALESCE(last_name, '')) STORED");

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->nullable()->after('last_name');
        });

        DB::statement('UPDATE users SET uuid = gen_random_uuid() WHERE uuid IS NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('document_type_id')->nullable();
            $table->string('document_number', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->unsignedBigInteger('gender_id')->nullable();
            $table->unsignedBigInteger('nationality_id')->nullable();
            $table->string('phone_primary', 20)->nullable();
            $table->string('phone_secondary', 20)->nullable();
            $table->string('profile_photo_url', 500)->nullable();
            $table->softDeletes();

            $table->foreign('document_type_id')->references('id')->on('document_types')->restrictOnDelete();
            $table->foreign('gender_id')->references('id')->on('genders')->restrictOnDelete();
            $table->foreign('nationality_id')->references('id')->on('countries')->restrictOnDelete();
        });

        DB::statement('CREATE UNIQUE INDEX users_document_unique ON users (document_type_id, document_number) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_document_unique');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['document_type_id']);
            $table->dropForeign(['gender_id']);
            $table->dropForeign(['nationality_id']);
            $table->dropColumn([
                'document_type_id',
                'document_number',
                'birth_date',
                'gender_id',
                'nationality_id',
                'phone_primary',
                'phone_secondary',
                'profile_photo_url',
                'deleted_at',
                'uuid',
            ]);
        });

        DB::statement('ALTER TABLE users DROP COLUMN IF EXISTS name');

        // nullable so we can populate from first_name/last_name before making it required
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->after('last_name');
        });

        DB::statement("UPDATE users SET name = TRIM(COALESCE(first_name, '') || ' ' || COALESCE(last_name, ''))");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
