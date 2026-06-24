<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates (or updates) the bootstrap super-admin from SUPER_ADMIN_* env vars.
 * Idempotent — safe to run once after setup.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $mobile = (string) env('SUPER_ADMIN_MOBILE', '01700000000');
        $password = (string) env('SUPER_ADMIN_PASSWORD', 'ChangeMe!2026');

        $user = User::updateOrCreate(
            ['mobile' => $mobile],
            [
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'email' => env('SUPER_ADMIN_EMAIL', 'admin@smarttolet.test'),
                'password' => Hash::make($password),
                'role' => Role::SuperAdmin->value,
                'is_phone_verified' => true,
                'is_email_verified' => true,
            ],
        );

        $user->subscriptions()->firstOrCreate(
            ['plan' => 'premium', 'status' => 'active'],
            ['started_at' => now(), 'expires_at' => null],
        );

        $this->command?->info("Super-admin ready: {$mobile}");
    }
}
