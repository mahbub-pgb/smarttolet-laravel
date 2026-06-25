<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardListingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validListingPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'apartment',
            'title' => 'Spacious 2-bed in Dhanmondi',
            'description' => 'A bright apartment close to everything.',
            'rent' => 25000,
            'advance_amount' => 50000,
            'available_from' => '2026-07-01',
            'latitude' => 23.7461,
            'longitude' => 90.3742,
            'area_name' => 'Dhanmondi',
            'address' => '12, Dhanmondi, Dhaka',
            'bedrooms' => 2,
            'bathrooms' => 2,
            'area_sqft' => 1100,
            'balconies' => 1,
            'floor_number' => 4,
            'building_floors' => 8,
            'amenities' => ['wifi', 'lift', 'parking'],
            'occupancy_rules' => ['family_only'],
            'video_tour_url' => 'https://youtube.com/watch?v=abc123',
        ], $overrides);
    }

    public function test_create_form_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->get(route('dashboard.listings.create'))
            ->assertOk()
            ->assertSee('Add a new listing');
    }

    public function test_edit_form_renders_for_owner(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->ownedBy($user)->create();

        $this->actingAs($user, 'web')
            ->get(route('dashboard.listings.edit', $listing))
            ->assertOk()
            ->assertSee('Edit listing');
    }

    public function test_user_submitting_for_review_creates_a_pending_listing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->post(route('dashboard.listings.store'), $this->validListingPayload(['as_draft' => 0]))
            ->assertRedirect(route('dashboard'));

        $listing = Listing::where('owner_id', $user->id)->firstOrFail();

        $this->assertSame(Listing::STATUS_PENDING, $listing->status);
        $this->assertSame(50000, $listing->advance_amount);
        $this->assertSame(['family_only'], $listing->occupancy_rules);
        $this->assertSame(1, $listing->balconies);
    }

    public function test_user_can_save_a_draft(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->post(route('dashboard.listings.store'), $this->validListingPayload(['as_draft' => 1]))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(Listing::STATUS_DRAFT, Listing::where('owner_id', $user->id)->value('status'));
    }

    public function test_new_listing_is_not_publicly_visible_until_approved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->post(route('dashboard.listings.store'), $this->validListingPayload(['as_draft' => 0]));

        // Not visible on the public listings API while pending.
        $this->assertSame(0, Listing::query()->publiclyVisible()->count());

        // After an admin approves it, it becomes publicly visible.
        $listing = Listing::where('owner_id', $user->id)->firstOrFail();
        $listing->update(['status' => Listing::STATUS_APPROVED, 'approved_at' => now(), 'expires_at' => now()->addDays(30)]);

        $this->assertSame(1, Listing::query()->publiclyVisible()->count());
    }

    public function test_user_cannot_self_publish_via_a_forged_status_field(): void
    {
        $user = User::factory()->create();

        // Even if "status=approved" is injected, it is ignored — the form has no
        // such field and the service forces draft/pending for owners.
        $this->actingAs($user, 'web')
            ->post(route('dashboard.listings.store'), $this->validListingPayload([
                'as_draft' => 0,
                'status' => 'approved',
            ]));

        $this->assertSame(Listing::STATUS_PENDING, Listing::where('owner_id', $user->id)->value('status'));
    }

    public function test_user_cannot_edit_another_users_listing(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $listing = Listing::factory()->ownedBy($owner)->create();

        $this->actingAs($intruder, 'web')
            ->get(route('dashboard.listings.edit', $listing))
            ->assertForbidden();

        $this->actingAs($intruder, 'web')
            ->put(route('dashboard.listings.update', $listing), $this->validListingPayload(['as_draft' => 0]))
            ->assertForbidden();
    }

    public function test_editing_an_approved_listing_sends_it_back_to_pending(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->ownedBy($user)->create(['status' => Listing::STATUS_APPROVED]);

        $this->actingAs($user, 'web')
            ->put(route('dashboard.listings.update', $listing), $this->validListingPayload([
                'as_draft' => 0,
                'title' => 'Updated title triggers re-review',
            ]))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(Listing::STATUS_PENDING, $listing->fresh()->status);
    }

    public function test_user_can_update_their_profile(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $this->actingAs($user, 'web')
            ->put(route('dashboard.profile.update'), [
                'name' => 'New Name',
                'occupation' => 'Engineer',
                'area_preferences' => 'Dhanmondi, Banani',
            ])
            ->assertRedirect(route('dashboard.profile'));

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('Engineer', $user->occupation);
        $this->assertSame(['Dhanmondi', 'Banani'], $user->area_preferences);
    }
}
