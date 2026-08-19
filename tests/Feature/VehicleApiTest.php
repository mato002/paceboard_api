<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VehicleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_nickname_is_saved(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/vehicles', [
            'manufacturer' => 'Toyota',
            'model' => 'Corolla',
            'nickname' => 'Daily Runner',
        ]);

        $response->assertCreated()
            ->assertJsonPath('nickname', 'Daily Runner');

        $this->assertDatabaseHas('vehicles', [
            'user_id' => $user->id,
            'nickname' => 'Daily Runner',
        ]);
    }

    public function test_vehicle_index_includes_trip_stats(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'manufacturer' => 'Mazda',
            'model' => 'CX-5',
            'registration_number' => 'KAA 001A',
        ]);

        $vehicle->trips()->create([
            'user_id' => $user->id,
            'name' => 'Test trip',
            'distance' => 25.5,
            'duration_seconds' => 1800,
            'average_speed' => 50,
            'top_speed' => 80,
            'score' => 90,
            'started_at' => now()->subHour(),
            'ended_at' => now(),
        ]);

        $response = $this->getJson('/api/vehicles');

        $response->assertOk()
            ->assertJsonPath('0.trips_count', 1)
            ->assertJsonPath('0.total_distance', 25.5)
            ->assertJsonPath('0.top_speed', 80)
            ->assertJsonStructure(['0' => ['service' => ['km_until_service', 'service_due']]]);
    }
}
