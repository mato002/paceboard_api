<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripShareAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_owner_cannot_share_completed_trip(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $trip = Trip::create([
            'user_id' => $owner->id,
            'distance' => 12,
            'score' => 85,
            'started_at' => now()->subHour(),
            'ended_at' => now(),
        ]);

        $token = $other->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/trips/{$trip->id}/share")
            ->assertForbidden();
    }

    public function test_owner_can_share_completed_trip(): void
    {
        $owner = User::factory()->create();

        $trip = Trip::create([
            'user_id' => $owner->id,
            'distance' => 12,
            'score' => 85,
            'started_at' => now()->subHour(),
            'ended_at' => now(),
        ]);

        $token = $owner->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/trips/{$trip->id}/share")
            ->assertOk()
            ->assertJsonStructure(['share_url', 'share_text']);
    }
}
