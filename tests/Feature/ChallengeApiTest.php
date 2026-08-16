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
            ->assertJsonPath('data.0.challenge.id', $challenge->id);
    }
}
