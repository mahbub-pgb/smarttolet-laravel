<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Listing;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminListingManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => Role::Admin->value]);
    }

    public function test_admin_can_view_the_manage_listings_page(): void
    {
        $listing = Listing::factory()->pending()->create(['title' => 'Needs review flat']);

        $this->actingAs($this->admin(), 'web')
            ->get(route('admin.listings.index'))
            ->assertOk()
            ->assertSee('Manage Listings')
            ->assertSee('Needs review flat');
    }

    public function test_non_admin_cannot_access_manage_listings(): void
    {
        $user = User::factory()->create(['role' => Role::User->value]);

        $this->actingAs($user, 'web')
            ->get(route('admin.listings.index'))
            ->assertForbidden();
    }

    public function test_admin_can_approve_a_pending_listing(): void
    {
        $listing = Listing::factory()->pending()->create();

        $this->actingAs($this->admin(), 'web')
            ->post(route('admin.listings.approve', $listing))
            ->assertRedirect();

        $this->assertSame(Listing::STATUS_APPROVED, $listing->fresh()->status);
        $this->assertSame(1, Listing::query()->publiclyVisible()->whereKey($listing->id)->count());
    }

    public function test_admin_can_reject_a_listing_with_a_message(): void
    {
        $listing = Listing::factory()->pending()->create();

        $this->actingAs($this->admin(), 'web')
            ->post(route('admin.listings.reject', $listing), ['reason' => 'Photos do not match the property.'])
            ->assertRedirect();

        $fresh = $listing->fresh();
        $this->assertSame(Listing::STATUS_REJECTED, $fresh->status);
        $this->assertSame('Photos do not match the property.', $fresh->rejection_reason);
    }

    public function test_rejection_requires_a_message(): void
    {
        $listing = Listing::factory()->pending()->create();

        $this->actingAs($this->admin(), 'web')
            ->post(route('admin.listings.reject', $listing), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame(Listing::STATUS_PENDING, $listing->fresh()->status);
    }

    public function test_owner_sees_the_rejection_message_on_their_dashboard(): void
    {
        $user = User::factory()->create();
        Listing::factory()->ownedBy($user)->create([
            'status' => Listing::STATUS_REJECTED,
            'rejection_reason' => 'Please add a valid address.',
        ]);

        $this->actingAs($user, 'web')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Please add a valid address.');
    }

    public function test_admin_can_move_a_listing_to_draft(): void
    {
        $listing = Listing::factory()->create(['status' => Listing::STATUS_APPROVED]);

        $this->actingAs($this->admin(), 'web')
            ->post(route('admin.listings.draft', $listing))
            ->assertRedirect();

        $this->assertSame(Listing::STATUS_DRAFT, $listing->fresh()->status);
    }

    public function test_admin_can_delete_a_listing(): void
    {
        $listing = Listing::factory()->create();

        $this->actingAs($this->admin(), 'web')
            ->delete(route('admin.listings.destroy', $listing))
            ->assertRedirect();

        $this->assertSoftDeleted('listings', ['id' => $listing->id]);
    }

    public function test_status_filter_narrows_the_list(): void
    {
        Listing::factory()->pending()->create(['title' => 'Pending one']);
        Listing::factory()->create(['title' => 'Approved one', 'status' => Listing::STATUS_APPROVED]);

        $this->actingAs($this->admin(), 'web')
            ->get(route('admin.listings.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('Pending one')
            ->assertDontSee('Approved one');
    }

    public function test_admin_created_listing_is_auto_published(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'web')->post(route('dashboard.listings.store'), [
            'type' => 'apartment',
            'title' => 'Admin published flat',
            'description' => 'No review needed for admin listings.',
            'rent' => 30000,
            'latitude' => 23.75,
            'longitude' => 90.39,
            'area_name' => 'Gulshan',
            'address' => '1, Gulshan',
            'as_draft' => 0,
        ])->assertRedirect(route('dashboard'));

        $listing = Listing::where('owner_id', $admin->id)->firstOrFail();
        $this->assertSame(Listing::STATUS_APPROVED, $listing->status);
        $this->assertNotNull($listing->approved_at);
        $this->assertSame(1, Listing::query()->publiclyVisible()->whereKey($listing->id)->count());
    }

    public function test_regular_user_listing_still_requires_review(): void
    {
        $user = User::factory()->create(['role' => Role::User->value]);

        $this->actingAs($user, 'web')->post(route('dashboard.listings.store'), [
            'type' => 'apartment',
            'title' => 'Normal user flat',
            'description' => 'Should go to pending.',
            'rent' => 12000,
            'latitude' => 23.75,
            'longitude' => 90.39,
            'area_name' => 'Mirpur',
            'address' => '1, Mirpur',
            'as_draft' => 0,
        ]);

        $this->assertSame(Listing::STATUS_PENDING, Listing::where('owner_id', $user->id)->value('status'));
    }

    public function test_admin_can_preview_a_pending_listing(): void
    {
        $listing = Listing::factory()->pending()->create(['title' => 'Hidden pending flat']);

        $this->actingAs($this->admin(), 'web')
            ->get(route('listings.show', $listing->slug))
            ->assertOk()
            ->assertSee('Hidden pending flat')
            ->assertSee('Preview');
    }

    public function test_admin_preview_fragment_renders_listing_details(): void
    {
        $listing = Listing::factory()->pending()->create([
            'title' => 'Pending modal preview',
            'description' => 'Details shown inside the admin modal.',
        ]);

        $this->actingAs($this->admin(), 'web')
            ->get(route('admin.listings.preview', $listing))
            ->assertOk()
            ->assertSee('Pending modal preview')
            ->assertSee('Approve &amp; publish', false)
            ->assertDontSee('<!DOCTYPE', false); // fragment only, no layout
    }

    public function test_non_admin_cannot_load_preview_fragment(): void
    {
        $listing = Listing::factory()->pending()->create();
        $user = User::factory()->create(['role' => Role::User->value]);

        $this->actingAs($user, 'web')
            ->get(route('admin.listings.preview', $listing))
            ->assertForbidden();
    }

    public function test_guest_cannot_view_a_pending_listing(): void
    {
        $listing = Listing::factory()->pending()->create();

        $this->get(route('listings.show', $listing->slug))->assertNotFound();
    }

    public function test_youtube_url_renders_as_an_embed(): void
    {
        $listing = Listing::factory()->create([
            'video_tour_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);

        $this->get(route('listings.show', $listing->slug))
            ->assertOk()
            ->assertSee('youtube.com/embed/dQw4w9WgXcQ');
    }

    public function test_owner_can_attach_a_library_image_when_creating_a_listing(): void
    {
        $user = User::factory()->create();
        $media = Media::create([
            'owner_id' => $user->id,
            'url' => 'https://example.test/storage/listings/lib.jpg',
            'public_id' => null,
            'disk' => 'public',
            'type' => 'image',
        ]);

        $this->actingAs($user, 'web')->post(route('dashboard.listings.store'), [
            'type' => 'apartment',
            'title' => 'With a library photo',
            'description' => 'Reusing an existing image from the library.',
            'rent' => 15000,
            'latitude' => 23.75,
            'longitude' => 90.39,
            'area_name' => 'Dhanmondi',
            'address' => '1, Dhanmondi',
            'picked' => [$media->id],
            'as_draft' => 0,
        ])->assertRedirect(route('dashboard'));

        $listing = Listing::where('owner_id', $user->id)->firstOrFail();
        $this->assertCount(1, $listing->images);
        $this->assertSame($media->url, $listing->images[0]['url']);
    }
}
