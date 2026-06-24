<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_user_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => Role::User->value]);

        $this->actingAsJwt($user)
            ->getJson('/api/v1/admin/dashboard')
            ->assertStatus(403)
            ->assertJson(['code' => 'insufficient_permission']);
    }

    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);

        $this->actingAsJwt($admin)
            ->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure(['data' => ['users', 'listings', 'reports']]);
    }

    public function test_admin_cannot_suspend_a_super_admin_rank_guard(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $superAdmin = User::factory()->create(['role' => Role::SuperAdmin->value]);

        $this->actingAsJwt($admin)
            ->patchJson("/api/v1/admin/users/{$superAdmin->id}", ['is_suspended' => true])
            ->assertStatus(403)
            ->assertJson(['code' => 'rank_violation']);
    }

    public function test_admin_cannot_assign_a_role_equal_or_above_their_own(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $target = User::factory()->create(['role' => Role::User->value]);

        $this->actingAsJwt($admin)
            ->patchJson("/api/v1/admin/users/{$target->id}", ['role' => 'admin'])
            ->assertStatus(403)
            ->assertJson(['code' => 'rank_violation']);
    }

    public function test_admin_can_promote_a_user_to_moderator(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $target = User::factory()->create(['role' => Role::User->value]);

        $this->actingAsJwt($admin)
            ->patchJson("/api/v1/admin/users/{$target->id}", ['role' => 'moderator'])
            ->assertOk();

        $this->assertSame('moderator', $target->fresh()->role->value);
    }
}
