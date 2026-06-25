<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            // Basic info
            $table->unsignedInteger('advance_amount')->nullable()->after('rent');
            $table->date('available_from')->nullable()->after('advance_amount');

            // Details
            $table->unsignedInteger('area_sqft')->nullable()->after('bathrooms');
            $table->unsignedSmallInteger('balconies')->default(0)->after('area_sqft');
            $table->smallInteger('floor_number')->nullable()->after('balconies');
            $table->unsignedSmallInteger('building_floors')->nullable()->after('floor_number');

            // Occupancy & rules (["family_only", "bachelor_allowed", ...])
            $table->json('occupancy_rules')->nullable()->after('amenities');

            // Media
            $table->string('video_tour_url')->nullable()->after('images');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn([
                'advance_amount',
                'available_from',
                'area_sqft',
                'balconies',
                'floor_number',
                'building_floors',
                'occupancy_rules',
                'video_tour_url',
            ]);
        });
    }
};
