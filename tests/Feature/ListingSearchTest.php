<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_paginated_envelope(): void
    {
        Listing::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/listings');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'meta' => ['page', 'limit', 'total', 'totalPages', 'hasNextPage', 'hasPrevPage'],
            ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_only_approved_listings_are_publicly_visible(): void
    {
        Listing::factory()->create(['title' => 'Visible approved flat']);
        Listing::factory()->pending()->create(['title' => 'Hidden pending flat']);

        $response = $this->getJson('/api/v1/listings')->assertOk();

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('Visible approved flat'));
        $this->assertFalse($titles->contains('Hidden pending flat'));
    }

    public function test_keyword_filter_narrows_results(): void
    {
        Listing::factory()->create(['title' => 'Cozy studio', 'area_name' => 'Banani']);
        Listing::factory()->create(['title' => 'Big house', 'area_name' => 'Mirpur']);

        $response = $this->getJson('/api/v1/listings?area=Banani')->assertOk();

        $areas = collect($response->json('data'))->pluck('area_name')->unique();
        $this->assertEquals(['Banani'], $areas->values()->all());
    }

    public function test_geo_radius_search_returns_nearby_listings(): void
    {
        // Near Gulshan.
        Listing::factory()->create(['latitude' => 23.7925, 'longitude' => 90.4078, 'title' => 'Near Gulshan']);
        // Far away (Chittagong-ish).
        Listing::factory()->create(['latitude' => 22.3569, 'longitude' => 91.7832, 'title' => 'Far Chittagong']);

        $response = $this->getJson('/api/v1/listings?lat=23.7925&lng=90.4078&radius=5')->assertOk();

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('Near Gulshan'));
        $this->assertFalse($titles->contains('Far Chittagong'));
    }

    public function test_show_increments_view_count(): void
    {
        $listing = Listing::factory()->create();

        $this->getJson("/api/v1/listings/{$listing->slug}")->assertOk();

        $this->assertSame(1, $listing->fresh()->view_count);
    }

    public function test_plan_limit_blocks_excess_listings(): void
    {
        $user = User::factory()->create();
        // Free plan default allows 2 active listings.
        Listing::factory()->count(2)->ownedBy($user)->create();

        $this->actingAsJwt($user)
            ->postJson('/api/v1/listings', [
                'title' => 'Third listing',
                'description' => 'Too many for the free plan.',
                'type' => 'apartment',
                'rent' => 12000,
                'area_name' => 'Dhanmondi',
                'address' => '12, Dhanmondi, Dhaka',
            ])
            ->assertStatus(403)
            ->assertJson(['code' => 'plan_limit_reached']);
    }
}
