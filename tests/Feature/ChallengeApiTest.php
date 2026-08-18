<?php

namespace Tests\Feature;

use App\Models\Challenge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChallengeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_join_challenge_and_see_progress(): void
    {
        $this->apiUser();

        $challenge = Challenge::create([
            'title' => 'Drive 50 KM',
            'type' => 'distance',
            'target_value' => 50,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->postJson("/api/challenges/{$challenge->id}/join")
            ->assertOk();

        $this->getJson('/api/challenges/mine')
            ->assertOk()
            ->assertJsonPath('data.0.id', $challenge->id)
            ->assertJsonPath('data.0.joined', true)
            ->assertJsonPath('data.0.target_label', 'Drive 50 km');

        $this->getJson('/api/challenges/summary')
            ->assertOk()
            ->assertJsonPath('active', 1);

        $this->getJson("/api/challenges/{$challenge->id}")
            ->assertOk()
            ->assertJsonPath('challenge.status', 'joined');
    }
}
