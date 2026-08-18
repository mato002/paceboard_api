<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_and_view_profile_photo(): void
    {
        Storage::fake('public');
        $user = $this->apiUser();
        $file = UploadedFile::fake()->image('me.jpg', 400, 400);

        $this->post('/api/profile/avatar', [
            'avatar' => $file,
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('avatar_url', fn ($url) => is_string($url) && str_contains($url, '/api/users/'.$user->id.'/avatar'));

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);

        $this->get('/api/users/'.$user->id.'/avatar')->assertOk();
    }

    public function test_avatar_upload_requires_an_image(): void
    {
        $this->apiUser();

        $this->postJson('/api/profile/avatar', [])
            ->assertStatus(422);
    }
}
