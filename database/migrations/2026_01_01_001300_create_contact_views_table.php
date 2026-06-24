<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('viewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('viewer_fingerprint', 64)->nullable(); // hashed ip+ua for guests
            $table->timestamps();

            // Dedupe per viewer (registered or guest fingerprint).
            $table->unique(['listing_id', 'viewer_id']);
            $table->unique(['listing_id', 'viewer_fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_views');
    }
};
