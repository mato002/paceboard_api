<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PhoneVerificationService;
use Illuminate\Http\Request;

class PhoneVerificationController extends Controller
{
    public function __construct(private PhoneVerificationService $verification) {}

    public function send(Request $request)
    {
        $request->validate(['phone' => 'required|string|max:20']);

        $this->verification->sendCode($request->user(), $request->phone);

        return response()->json(['message' => 'Verification code sent']);
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $this->verification->verify($request->user(), $request->code);

        return response()->json([
            'message' => 'Phone verified successfully',
            'phone_verified_at' => $request->user()->fresh()->phone_verified_at,
        ]);
    }
}
