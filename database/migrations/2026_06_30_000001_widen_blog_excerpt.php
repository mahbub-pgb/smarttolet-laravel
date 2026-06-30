<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only MySQL enforces VARCHAR length; SQLite stores all strings as TEXT
        // so the test driver needs no change (and avoids change() quirks).
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('excerpt', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('excerpt')->nullable()->change();
        });
    }
};
