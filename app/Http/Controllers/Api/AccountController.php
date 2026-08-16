<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function export(Request $request)
    {
        $user = $request->user()->load(['trips', 'vehicles', 'communityReports']);

        return response()->json([
            'exported_at' => now()->toIso8601String(),
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'country' => $user->country,
                'county' => $user->county,
                'bio' => $user->bio,
                'total_distance' => $user->total_distance,
                'driving_hours' => $user->driving_hours,
                'created_at' => $user->created_at,
            ],
            'trips' => $user->trips,
            'vehicles' => $user->vehicles,
            'community_reports' => $user->communityReports,
        ]);
    }

    public function acceptPrivacy(Request $request)
    {
        $request->user()->update(['privacy_accepted_at' => now()]);

        return response()->json(['message' => 'Privacy policy accepted']);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if (! \Hash::check($request->password, $request->user()->password)) {
            return response()->json(['message' => 'Incorrect password'], 422);
        }

        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Account deleted successfully']);
    }
}
