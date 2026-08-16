<?php

namespace App\Services;

use App\Models\PhoneVerification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $phone, string $message): bool
    {
        $driver = config('paceboard.sms.driver', 'log');

        return match ($driver) {
            'africas_talking' => $this->sendViaAfricasTalking($phone, $message),
            'twilio' => $this->sendViaTwilio($phone, $message),
            default => $this->logSms($phone, $message),
        };
    }

    public function sendOtp(string $phone, string $code): bool
    {
        return $this->send($phone, "Your PaceBoard verification code is: {$code}. Valid for 10 minutes.");
    }

    private function logSms(string $phone, string $message): bool
    {
        Log::info('SMS sent', ['phone' => $phone, 'message' => $message]);

        return true;
    }

    private function sendViaAfricasTalking(string $phone, string $message): bool
    {
        $config = config('paceboard.sms.africas_talking');

        if (! $config['api_key'] || ! $config['username']) {
            return $this->logSms($phone, $message);
        }

        $response = Http::withHeaders([
            'apiKey' => $config['api_key'],
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ])->asForm()->post('https://api.africastalking.com/version1/messaging', [
            'username' => $config['username'],
            'to' => $phone,
            'message' => $message,
            'from' => $config['from'],
        ]);

        return $response->successful();
    }

    private function sendViaTwilio(string $phone, string $message): bool
    {
        $config = config('paceboard.sms.twilio');

        if (! $config['sid'] || ! $config['token']) {
            return $this->logSms($phone, $message);
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$config['sid']}/Messages.json";

        $response = Http::withBasicAuth($config['sid'], $config['token'])
            ->asForm()
            ->post($url, [
                'To' => $phone,
                'From' => $config['from'],
                'Body' => $message,
            ]);

        return $response->successful();
    }
}
