<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Database-backed OTP storage (replaces the Redis store). One row per OTP key
 * ("otp:<purpose>:<id>") and per cooldown key ("...:cooldown"), each with its
 * own expiry. Only the SHA-256 hash is stored, never the plaintext code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('hash')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
