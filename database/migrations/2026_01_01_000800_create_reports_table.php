<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->string('reason');
            $table->text('details')->nullable();
            $table->enum('status', ['open', 'resolved', 'dismissed'])->default('open')->index();
            $table->foreignId('resolver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            // One report per user + listing.
            $table->unique(['reporter_id', 'listing_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
