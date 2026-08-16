<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SosAlert;
use App\Models\Trip;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class SosController extends Controller
{
    public function trigger(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'trip_id' => 'nullable|exists:trips,id',
            'message' => 'nullable|string|max:500',
        ]);

        if (! empty($validated['trip_id'])) {
            $trip = Trip::findOrFail($validated['trip_id']);
            if ($trip->user_id !== $request->user()->id) {
                abort(403, 'Trip does not belong to you');
            }
        }

        $alert = SosAlert::create([
            'user_id' => $request->user()->id,
            'trip_id' => $validated['trip_id'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'message' => $validated['message'] ?? 'Emergency SOS triggered',
            'status' => 'active',
        ]);

        ActivityLog::record('sos_triggered', $request->user()->id, [
            'alert_id' => $alert->id,
            'lat' => $validated['latitude'],
            'lng' => $validated['longitude'],
        ]);

        $admins = User::where('is_admin', true)->get();
        foreach ($admins as $admin) {
            UserNotification::create([
                'user_id' => $admin->id,
                'type' => 'sos_alert',
                'title' => 'SOS Emergency Alert',
                'body' => "{$request->user()->name} triggered an SOS alert.",
                'data' => ['alert_id' => $alert->id, 'user_id' => $request->user()->id],
            ]);
        }

        return response()->json([
            'message' => 'SOS alert sent. Help is on the way.',
            'alert' => $alert,
        ], 201);
    }
}
