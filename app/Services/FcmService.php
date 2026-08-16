<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        if (! config('paceboard.fcm.enabled') || ! $user->fcm_token) {
            return false;
        }

        return $this->send($user->fcm_token, $title, $body, $data);
    }

    public function send(string $token, string $title, string $body, array $data = []): bool
    {
        $serverKey = config('paceboard.fcm.server_key');

        if (! $serverKey) {
            Log::info('FCM skipped (no server key)', compact('title', 'body'));

            return false;
        }

        $response = Http::withHeaders([
            'Authorization' => 'key='.$serverKey,
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
            ],
            'data' => $data,
            'priority' => 'high',
        ]);

        if (! $response->successful()) {
            Log::warning('FCM send failed', ['response' => $response->body()]);

            return false;
        }

        return true;
    }

    public function broadcast(array $tokens, string $title, string $body, array $data = []): int
    {
        $sent = 0;

        foreach (array_chunk($tokens, 500) as $chunk) {
            $serverKey = config('paceboard.fcm.server_key');
            if (! $serverKey) {
                break;
            }

            $response = Http::withHeaders([
                'Authorization' => 'key='.$serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'registration_ids' => $chunk,
                'notification' => ['title' => $title, 'body' => $body],
                'data' => $data,
            ]);

            if ($response->successful()) {
                $sent += count($chunk);
            }
        }

        return $sent;
    }
}
