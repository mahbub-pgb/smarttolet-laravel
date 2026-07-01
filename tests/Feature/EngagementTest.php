<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\Listing;
use App\Models\Notification;
use App\Models\SavedSearch;
use App\Models\User;
use App\Services\Listing\ListingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EngagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_toggle_a_favorite(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create();

        // First toggle adds it.
        $this->actingAs($user, 'web')
            ->post(route('favorites.toggle', $listing))
            ->assertOk()
            ->assertJson(['favorited' => true]);

        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'listing_id' => $listing->id]);

        // Second toggle removes it.
        $this->actingAs($user, 'web')
            ->post(route('favorites.toggle', $listing))
            ->assertOk()
            ->assertJson(['favorited' => false]);

        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'listing_id' => $listing->id]);
    }

    public function test_saving_a_search_stores_the_active_filters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->post(route('dashboard.searches.store'), [
                'area' => 'Gulshan',
                'type' => 'apartment',
                'max_rent' => 15000,
                'notify' => '1',
            ])
            // "Save & run" lands on the public listings page with the filters applied.
            ->assertRedirect(route('listings.index', ['area' => 'Gulshan', 'type' => 'apartment', 'max_rent' => 15000]));

        $search = SavedSearch::where('user_id', $user->id)->firstOrFail();
        $this->assertTrue($search->notify);
        $this->assertSame('Gulshan', $search->params['area']);
        $this->assertSame('apartment', $search->params['type']);
    }

    public function test_approving_a_listing_alerts_matching_saved_searches(): void
    {
        $owner = User::factory()->create();
        $searcher = User::factory()->create();
        $otherSearcher = User::factory()->create();

        $listing = Listing::factory()->ownedBy($owner)->create([
            'status' => Listing::STATUS_PENDING,
            'area_name' => 'Gulshan',
            'type' => 'apartment',
            'rent' => 12000,
        ]);

        // Matches the listing → should be alerted.
        SavedSearch::create([
            'user_id' => $searcher->id,
            'name' => 'Gulshan apartments',
            'params' => ['area' => 'Gulshan', 'type' => 'apartment', 'max_rent' => 15000],
            'notify' => true,
        ]);

        // Different area → should NOT be alerted.
        SavedSearch::create([
            'user_id' => $otherSearcher->id,
            'name' => 'Dhanmondi homes',
            'params' => ['area' => 'Dhanmondi'],
            'notify' => true,
        ]);

        // The owner's own matching search must never self-alert.
        SavedSearch::create([
            'user_id' => $owner->id,
            'name' => 'My own area',
            'params' => ['area' => 'Gulshan'],
            'notify' => true,
        ]);

        app(ListingService::class)->moderate($listing, 'approve');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $searcher->id,
            'type' => 'listing_match',
        ]);
        $this->assertSame(0, Notification::where('user_id', $otherSearcher->id)->where('type', 'listing_match')->count());
        $this->assertSame(0, Notification::where('user_id', $owner->id)->where('type', 'listing_match')->count());
    }

    public function test_saved_tab_lists_favorite_listings(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create(['status' => Listing::STATUS_APPROVED, 'approved_at' => now(), 'expires_at' => now()->addDays(30)]);
        Favorite::create(['user_id' => $user->id, 'listing_id' => $listing->id]);

        $this->actingAs($user, 'web')
            ->get(route('dashboard.saved'))
            ->assertOk()
            ->assertSee($listing->title);
    }

    public function test_searches_tab_lists_saved_searches(): void
    {
        $user = User::factory()->create();
        SavedSearch::create(['user_id' => $user->id, 'name' => 'Gulshan apartments', 'params' => ['area' => 'Gulshan'], 'notify' => true]);

        $this->actingAs($user, 'web')
            ->get(route('dashboard.searches'))
            ->assertOk()
            ->assertSee('Create a search')
            ->assertSee('Gulshan apartments');
    }
}
