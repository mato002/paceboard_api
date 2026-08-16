<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_maintenance_mode_blocks_regular_users(): void
    {
        AppSetting::setValue('maintenance_mode', true, 'boolean', 'app');
        AppSetting::setValue('maintenance_message', 'Down for maintenance', 'string', 'app');

        $this->apiUser();

        $this->getJson('/api/dashboard')
            ->assertStatus(503)
            ->assertJsonPath('maintenance_mode', true);
    }

    public function test_admin_bypasses_maintenance_mode(): void
    {
        AppSetting::setValue('maintenance_mode', true, 'boolean', 'app');

        $this->adminUser();

        $this->getJson('/api/dashboard')->assertOk();
    }
}
