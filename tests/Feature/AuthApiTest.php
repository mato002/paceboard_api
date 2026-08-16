<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_fetch_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'driver@paceboard.test',
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'driver@paceboard.test',
            'password' => 'password',
        ]);

        $login->assertOk()
            ->assertJsonStructure(['access_token', 'user']);

        $token = $login->json('access_token');

        $this->withToken($token)
            ->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('email', $user->email);
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 11; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => 'missing@example.com',
                'password' => 'wrong',
            ]);
        }

        $response->assertStatus(429);
    }
}
