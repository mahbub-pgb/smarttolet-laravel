<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();

            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');

            // type/category, e.g. apartment, room, sublet, office, shop.
            $table->string('type', 50)->index();
            $table->string('category', 50)->nullable()->index();

            $table->unsignedInteger('rent');
            $table->unsignedSmallInteger('bedrooms')->default(0);
            $table->unsignedSmallInteger('bathrooms')->default(0);

            $table->string('area_name')->index();
            $table->string('address');

            // Decimal geo (always present). POINT + SPATIAL added below on MySQL.
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->json('images')->nullable();      // [{url, public_id}, ...]
            $table->json('amenities')->nullable();    // ["wifi", "lift", ...]

            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'rented'])
                ->default('pending')->index();
            $table->string('rejection_reason')->nullable();

            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('contact_view_count')->default(0);

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'type']);
            $table->index(['rent']);
            $table->index(['latitude', 'longitude']);
        });

        if (DB::getDriverName() === 'mysql') {
            // Spatial POINT (SRID 4326) + spatial index requires NOT NULL, so we
            // keep it nullable-friendly by populating it on write and using the
            // decimal columns as the source of truth for radius pre-filtering.
            DB::statement('ALTER TABLE listings ADD COLUMN geo POINT NULL');
            // FULLTEXT for keyword search across title/description/area.
            DB::statement('ALTER TABLE listings ADD FULLTEXT listings_fulltext (title, description, area_name)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
