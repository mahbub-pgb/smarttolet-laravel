<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Permission;
use App\Enums\Role;
use App\Support\ApiResponse;
use PHPUnit\Framework\TestCase;

class ResponseEnvelopeTest extends TestCase
{
    public function test_success_envelope_shape(): void
    {
        $response = ApiResponse::success(['x' => 1], 'Done', 201, ['page' => 1]);

        $this->assertSame(201, $response->getStatusCode());

        $payload = $response->getData(true);
        $this->assertTrue($payload['success']);
        $this->assertSame('Done', $payload['message']);
        $this->assertSame(['x' => 1], $payload['data']);
        $this->assertSame(['page' => 1], $payload['meta']);
    }

    public function test_error_envelope_shape(): void
    {
        $response = ApiResponse::error('Nope', 422, 'validation_failed', ['field' => ['bad']]);

        $this->assertSame(422, $response->getStatusCode());

        $payload = $response->getData(true);
        $this->assertFalse($payload['success']);
        $this->assertSame('validation_failed', $payload['code']);
        $this->assertArrayHasKey('details', $payload);
        $this->assertArrayNotHasKey('data', $payload);
    }

    public function test_role_ranks_are_ordered(): void
    {
        $this->assertLessThan(Role::Moderator->rank(), Role::User->rank());
        $this->assertLessThan(Role::Admin->rank(), Role::Moderator->rank());
        $this->assertLessThan(Role::SuperAdmin->rank(), Role::Admin->rank());
    }

    public function test_permission_inheritance(): void
    {
        // Admin inherits moderator permissions.
        $adminPerms = Permission::forRole(Role::Admin);
        $this->assertContains(Permission::ReviewListings, $adminPerms); // moderator-level
        $this->assertContains(Permission::ManageSettings, $adminPerms); // admin-level

        // super_admin-only permissions are NOT granted to admin.
        $this->assertNotContains(Permission::ManagePayments, $adminPerms);

        // Normal users have no management permissions.
        $this->assertSame([], Permission::forRole(Role::User));
    }
}
