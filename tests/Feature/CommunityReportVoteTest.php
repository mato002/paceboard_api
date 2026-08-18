<?php

namespace Tests\Feature;

use App\Models\CommunityReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityReportVoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_dispute_report(): void
    {
        $user = User::factory()->create();
        $report = CommunityReport::create([
            'user_id' => $user->id,
            'type' => 'pothole',
            'latitude' => -1.29,
            'longitude' => 36.82,
            'is_active' => true,
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/reports/{$report->id}/dispute", ['reason' => 'Not there'])
            ->assertOk();

        $this->assertDatabaseHas('report_verifications', [
            'user_id' => $user->id,
            'community_report_id' => $report->id,
            'vote' => 'down',
        ]);
    }

    public function test_nearby_includes_confidence_and_ahead_flag(): void
    {
        $user = $this->apiUser();
        CommunityReport::create([
            'user_id' => $user->id,
            'type' => 'speed_camera',
            'latitude' => -1.2860,
            'longitude' => 36.8219,
            'road_name' => 'Uhuru Highway',
            'is_active' => true,
            'verification_score' => 4,
            'confirmations_count' => 8,
        ]);

        $this->getJson('/api/reports/nearby?lat=-1.2921&lng=36.8219&heading=0&road_name=Uhuru%20Highway')
            ->assertOk()
            ->assertJsonPath('0.confidence', fn ($v) => (int) $v >= 50)
            ->assertJsonPath('0.ahead', true);
    }
}
