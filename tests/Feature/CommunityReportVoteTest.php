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
}
