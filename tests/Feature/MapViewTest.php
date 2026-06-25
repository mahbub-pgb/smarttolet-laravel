<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_plots_approved_geocoded_listings_with_details(): void
    {
        $visible = Listing::factory()->create([
            'title' => 'Map flat with details',
            'rent' => 18000,
            'bedrooms' => 3,
            'latitude' => 23.75,
            'longitude' => 90.39,
        ]);

        $response = $this->get(route('listings.map'))->assertOk();

        // The point payload includes the data shown in the info window + a link.
        $response->assertSee('Map flat with details');
        $response->assertSee('"rent":18000', false);
        $response->assertSee('"bedrooms":3', false);
        // The info-window "View details" link points at the single listing.
        $response->assertSee($visible->slug);
    }

    public function test_map_excludes_pending_and_ungeocoded_listings(): void
    {
        Listing::factory()->pending()->create(['title' => 'Hidden pending on map', 'latitude' => 23.7, 'longitude' => 90.4]);
        Listing::factory()->create(['title' => 'Approved but no coords', 'latitude' => null, 'longitude' => null]);

        $this->get(route('listings.map'))
            ->assertOk()
            ->assertDontSee('Hidden pending on map')
            ->assertDontSee('Approved but no coords');
    }
}
