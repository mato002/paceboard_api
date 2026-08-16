<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\FcmService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPushNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId,
        public string $title,
        public string $body,
        public array $data = []
    ) {}

    public function handle(FcmService $fcm): void
    {
        $user = User::find($this->userId);
        if ($user) {
            $fcm->sendToUser($user, $this->title, $this->body, $this->data);
        }
    }
}
