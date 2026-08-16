<?php

namespace App\Observers;

use App\Jobs\SendPushNotification;
use App\Models\UserNotification;

class UserNotificationObserver
{
    public function created(UserNotification $notification): void
    {
        SendPushNotification::dispatch(
            $notification->user_id,
            $notification->title,
            $notification->body ?? '',
            $notification->data ?? []
        );
    }
}
