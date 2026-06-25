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
