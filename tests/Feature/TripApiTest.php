<?php

namespace Tests\Feature;

use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_start_pause_resume_and_end_trip(): void
    {
        $user = $this->apiUser();

        $start = $this->postJson('/api/trips/start', [
            'name' => 'Test drive',
            'start_city' => 'Nairobi',
            'start_lat' => -1.2921,
            'start_lng' => 36.8219,
        ]);

        $start->assertOk();
        $tripId = $start->json('trip.id');

        $this->postJson("/api/trips/{$tripId}/sync", [
            'points' => [
                ['latitude' => -1.2921, 'longitude' => 36.8219, 'speed' => 40, 'recorded_at' => now()->subMinutes(2)->toIso8601String()],
                ['latitude' => -1.2800, 'longitude' => 36.8300, 'speed' => 50, 'recorded_at' => now()->subMinute()->toIso8601String()],
                ['latitude' => -1.2700, 'longitude' => 36.8400, 'speed' => 45, 'recorded_at' => now()->toIso8601String()],
            ],
        ])->assertOk();

        $this->postJson("/api/trips/{$tripId}/pause")->assertOk()
            ->assertJsonPath('trip.paused_at', fn ($v) => $v !== null);

        $this->postJson("/api/trips/{$tripId}/sync", ['points' => [
            ['latitude' => -1.26, 'longitude' => 36.85, 'speed' => 30, 'recorded_at' => now()->toIso8601String()],
        ]])->assertStatus(422);

        $this->postJson("/api/trips/{$tripId}/resume")->assertOk();

        $this->postJson("/api/trips/{$tripId}/end", [
            'end_city' => 'Thika',
            'end_lat' => -1.0333,
            'end_lng' => 37.0693,
        ])->assertOk()
            ->assertJsonPath('trip.ended_at', fn ($v) => $v !== null);

        $trip = Trip::find($tripId);
        $this->assertGreaterThan(0, $trip->distance);
        $this->assertNotNull($trip->drivingAnalysis);
    }

    public function test_cannot_start_two_active_trips(): void
    {
        $this->apiUser();

        $this->postJson('/api/trips/start')->assertOk();
        $this->postJson('/api/trips/start')->assertStatus(422);
    }

    public function test_offline_trip_sync_is_idempotent_and_preserves_kmh_speed(): void
    {
        $this->apiUser();
        $startedAt = now()->subHour()->startOfSecond();
        $endedAt = $startedAt->copy()->addMinutes(20);
        $payload = [
            'trips' => [[
                'local_id' => 'trip_test_123',
                'name' => 'Offline drive',
                'started_at' => $startedAt->toIso8601String(),
                'ended_at' => $endedAt->toIso8601String(),
                'points' => [
                    [
                        'latitude' => -1.2921,
                        'longitude' => 36.8219,
                        'speed' => 12,
                        'recorded_at' => $startedAt->toIso8601String(),
                    ],
                    [
                        'latitude' => -1.2821,
                        'longitude' => 36.8319,
                        'speed' => 18,
                        'recorded_at' => $endedAt->toIso8601String(),
                    ],
                ],
            ]],
        ];

        $this->postJson('/api/trips/offline-sync', $payload)
            ->assertOk()
            ->assertJsonPath('results.0.status', 'synced');
        $this->postJson('/api/trips/offline-sync', $payload)
            ->assertOk()
            ->assertJsonPath('results.0.status', 'synced');

        $this->assertDatabaseCount('trips', 1);
        $this->assertDatabaseCount('trip_points', 2);
        $this->assertSame(12.0, (float) Trip::first()->points()->oldest('id')->value('speed'));
    }

    public function test_online_trip_can_finish_from_offline_queue_without_duplicate_points(): void
    {
        $this->apiUser();
        $startedAt = now()->subMinutes(10)->startOfSecond();
        $firstPointAt = $startedAt->copy()->addMinute();
        $tripId = $this->postJson('/api/trips/start', [
            'name' => 'Connection lost',
        ])->assertOk()->json('trip.id');

        $firstPoint = [
            'latitude' => -1.2921,
            'longitude' => 36.8219,
            'speed' => 30,
            'recorded_at' => $firstPointAt->toIso8601String(),
        ];
        $this->postJson("/api/trips/{$tripId}/sync", [
            'points' => [$firstPoint],
        ])->assertOk();

        $payload = ['trips' => [[
            'local_id' => "server_trip_{$tripId}",
            'server_trip_id' => $tripId,
            'started_at' => $startedAt->toIso8601String(),
            'ended_at' => now()->startOfSecond()->toIso8601String(),
            'points' => [
                $firstPoint,
                [
                    'latitude' => -1.2821,
                    'longitude' => 36.8319,
                    'speed' => 35,
                    'recorded_at' => $firstPointAt->copy()->addMinute()->toIso8601String(),
                ],
            ],
        ]]];

        $this->postJson('/api/trips/offline-sync', $payload)->assertOk();
        $this->postJson('/api/trips/offline-sync', $payload)->assertOk();

        $trip = Trip::findOrFail($tripId);
        $this->assertNotNull($trip->ended_at);
        $this->assertCount(2, $trip->points);
    }

    public function test_dashboard_returns_recent_trips_and_monthly_stats(): void
    {
        $user = $this->apiUser();

        Trip::create([
            'user_id' => $user->id,
            'name' => 'Past trip',
            'distance' => 15,
            'score' => 90,
            'started_at' => now()->subDay(),
            'ended_at' => now()->subDay()->addHour(),
        ]);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'greeting', 'recent_trips', 'monthly_stats', 'weekly_distance',
            ]);
    }
}
