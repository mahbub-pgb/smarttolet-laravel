<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visitor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('visitor_fingerprint', 64)->nullable();
            $table->string('source', 60)->nullable(); // search, direct, share...
            $table->date('visited_on'); // for daily dedupe + reporting
            $table->timestamps();

            // One visit per visitor per listing per day.
            $table->unique(['listing_id', 'visitor_id', 'visited_on'], 'visit_user_day_unique');
            $table->unique(['listing_id', 'visitor_fingerprint', 'visited_on'], 'visit_fp_day_unique');
            $table->index(['listing_id', 'visited_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_visits');
    }
};
