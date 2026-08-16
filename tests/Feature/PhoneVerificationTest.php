<?php

namespace Tests\Feature;

use App\Models\PhoneVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_verify_phone_with_otp(): void
    {
        $user = $this->apiUser();

        $this->postJson('/api/phone/send-code', ['phone' => '0712345678'])
            ->assertOk();

        $code = PhoneVerification::where('phone', '+254712345678')->first()->code;

        $this->postJson('/api/phone/verify', ['code' => $code])
            ->assertOk()
            ->assertJsonStructure(['phone_verified_at']);

        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_invalid_otp_is_rejected(): void
    {
        $user = $this->apiUser(['phone' => '+254712345678']);

        PhoneVerification::create([
            'phone' => '+254712345678',
            'code' => '123456',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson('/api/phone/verify', ['code' => '000000'])
            ->assertStatus(422);
    }
}
