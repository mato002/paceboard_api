<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_does_not_issue_token_before_verification(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'New Driver',
            'email' => 'new@paceboard.test',
            'password' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonMissing(['access_token'])
            ->assertJsonPath('email_verification_required', true);
    }
}
