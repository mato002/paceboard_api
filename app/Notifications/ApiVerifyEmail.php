<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class ApiVerifyEmail extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);
        $id = $notifiable->getKey();
        $hash = sha1($notifiable->getEmailForVerification());

        return (new MailMessage)
            ->subject('Verify your PaceBoard email')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Tap the button below to verify your email and sign in to PaceBoard.')
            ->action('Verify Email Address', $verificationUrl)
            ->line('If the button does not work, open the PaceBoard app and enter:')
            ->line('User ID: '.$id)
            ->line('Verification hash: '.$hash)
            ->line('If you did not create an account, no further action is required.');
    }

    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
