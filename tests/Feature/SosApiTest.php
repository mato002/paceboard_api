<?php

namespace Tests\Feature;

use App\Models\SosAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SosApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_trigger_sos_alert(): void
    {
        User::factory()->create(['is_admin' => true, 'email' => 'admin@test.com']);

        $this->apiUser();

        $this->postJson('/api/sos', [
            'latitude' => -1.2921,
            'longitude' => 36.8219,
            'message' => 'Need help',
        ])->assertCreated()
            ->assertJsonPath('alert.status', 'active');

        $this->assertEquals(1, SosAlert::where('status', 'active')->count());
    }
}
