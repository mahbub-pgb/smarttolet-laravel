<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Mobile is the primary identity.
            $table->string('mobile', 20)->unique();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();

            $table->string('name')->nullable();
            $table->string('photo')->nullable();
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('occupation')->nullable();
            $table->string('nid', 50)->nullable();
            $table->string('address')->nullable();

            $table->enum('role', ['user', 'moderator', 'admin', 'super_admin'])->default('user')->index();

            $table->boolean('is_phone_verified')->default(false);
            $table->boolean('is_email_verified')->default(false);
            $table->boolean('is_suspended')->default(false)->index();
            $table->boolean('is_landlord_verified')->default(false);

            // Bumped to invalidate all outstanding refresh tokens.
            $table->unsignedBigInteger('token_version')->default(0);

            // Location (decimal columns always present; POINT added below on MySQL).
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Preferred areas (array of strings) and other JSON preferences.
            $table->json('area_preferences')->nullable();

            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // MySQL-only spatial column + index for proximity queries.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users ADD COLUMN location POINT NULL');
        }

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
