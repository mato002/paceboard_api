<?php

namespace App\Services;

use App\Models\PhoneVerification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PhoneVerificationService
{
    public function __construct(private SmsService $sms) {}

    public function sendCode(User $user, string $phone): void
    {
        $phone = $this->normalizePhone($phone);

        if (User::where('phone', $phone)->where('id', '!=', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'phone' => ['This phone number is already in use.'],
            ]);
        }

        $code = (string) random_int(100000, 999999);

        PhoneVerification::where('phone', $phone)->delete();

        PhoneVerification::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        $user->update(['phone' => $phone]);
        $this->sms->sendOtp($phone, $code);
    }

    public function verify(User $user, string $code): void
    {
        $phone = $user->phone;

        if (! $phone) {
            throw ValidationException::withMessages([
                'phone' => ['No phone number on file. Request a code first.'],
            ]);
        }

        $verification = PhoneVerification::where('phone', $phone)->latest()->first();

        if (! $verification || $verification->isExpired()) {
            throw ValidationException::withMessages([
                'code' => ['Verification code expired. Request a new one.'],
            ]);
        }

        if ($verification->attempts >= 5) {
            throw ValidationException::withMessages([
                'code' => ['Too many attempts. Request a new code.'],
            ]);
        }

        if ($verification->code !== $code) {
            $verification->increment('attempts');
            throw ValidationException::withMessages([
                'code' => ['Invalid verification code.'],
            ]);
        }

        DB::transaction(function () use ($user, $phone, $verification) {
            $user->update(['phone_verified_at' => now(), 'phone' => $phone]);
            $verification->delete();
        });
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '+254'.substr($phone, 1);
        }

        if (! str_starts_with($phone, '+')) {
            $phone = '+'.$phone;
        }

        return $phone;
    }
}
