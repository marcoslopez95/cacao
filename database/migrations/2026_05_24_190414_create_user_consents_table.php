<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_consents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('policy_version', 20);
            $table->boolean('accepts_data_processing');
            $table->boolean('accepts_image_use');
            $table->boolean('accepts_whatsapp_contact');
            $table->boolean('accepts_email_contact');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_consents');
    }
};
