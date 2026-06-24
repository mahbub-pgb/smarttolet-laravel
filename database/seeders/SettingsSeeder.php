<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'site_name', 'value' => ['v' => 'Smart To-Let'], 'is_secret' => false],
            ['key' => 'support_email', 'value' => ['v' => 'support@smarttolet.test'], 'is_secret' => false],
            ['key' => 'support_phone', 'value' => ['v' => '+8801700000000'], 'is_secret' => false],
            ['key' => 'maintenance_mode', 'value' => ['v' => false], 'is_secret' => false],
        ];

        foreach ($defaults as $row) {
            Setting::updateOrCreate(['key' => $row['key']], $row);
        }
    }
}
