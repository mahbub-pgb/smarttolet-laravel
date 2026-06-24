<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_a')->constrained('users')->cascadeOnDelete();
            $table->foreignId('participant_b')->constrained('users')->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('last_message_id')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            // One conversation per participant-pair + listing.
            $table->unique(['participant_a', 'participant_b', 'listing_id'], 'conversations_pair_listing_unique');
            $table->index('participant_a');
            $table->index('participant_b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
