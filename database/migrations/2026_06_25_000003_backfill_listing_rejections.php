<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed the rejection history with the single `rejection_reason` that existed
     * before the listing_rejections table, so admins/owners can still see the
     * message from listings rejected earlier.
     */
    public function up(): void
    {
        $listings = DB::table('listings')
            ->whereNotNull('rejection_reason')
            ->where('rejection_reason', '!=', '')
            ->get(['id', 'rejection_reason', 'updated_at']);

        foreach ($listings as $listing) {
            $alreadyLogged = DB::table('listing_rejections')
                ->where('listing_id', $listing->id)
                ->exists();

            if ($alreadyLogged) {
                continue;
            }

            $when = $listing->updated_at ?? now();

            DB::table('listing_rejections')->insert([
                'listing_id' => $listing->id,
                'moderator_id' => null,
                'reason' => $listing->rejection_reason,
                'created_at' => $when,
                'updated_at' => $when,
            ]);
        }
    }

    public function down(): void
    {
        // Data backfill — nothing to reverse.
    }
};
