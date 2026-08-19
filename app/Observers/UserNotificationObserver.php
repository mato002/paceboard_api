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
            array_filter(array_merge(
                ['type' => $notification->type],
                is_array($notification->data) ? $notification->data : []
            ), fn ($value) => $value !== null)
        );
    }
}
