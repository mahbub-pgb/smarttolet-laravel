<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Seeder;

class ListingSeeder extends Seeder
{
    public function run(): void
    {
        // A handful of landlords, each owning several approved listings, plus a
        // few pending ones to populate the moderation queue.
        $landlords = User::factory()->count(8)->create();

        foreach ($landlords as $landlord) {
            Listing::factory()->count(fake()->numberBetween(2, 5))->ownedBy($landlord)->create();
        }

        Listing::factory()->count(6)->pending()->create();
    }
}
