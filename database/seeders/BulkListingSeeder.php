<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds 5,000 approved listings spread across a pool of landlords. Slugs are
 * pre-set so the model's uniqueSlug() lookup is skipped (titles repeat heavily
 * at this volume, which would otherwise make that loop very slow).
 */
class BulkListingSeeder extends Seeder
{
    private const TOTAL = 5000;

    private const CHUNK = 250;

    public function run(): void
    {
        $landlords = User::factory()->count(50)->create();

        $created = 0;
        while ($created < self::TOTAL) {
            $count = min(self::CHUNK, self::TOTAL - $created);

            Listing::factory()
                ->count($count)
                ->state(new Sequence(fn (Sequence $s) => [
                    'owner_id' => $landlords->random()->id,
                    'slug' => 'listing-'.($created + $s->index + 1).'-'.Str::lower(Str::random(6)),
                ]))
                ->create();

            $created += $count;
            $this->command->getOutput()->writeln("  seeded {$created}/".self::TOTAL.' listings');
        }
    }
}
