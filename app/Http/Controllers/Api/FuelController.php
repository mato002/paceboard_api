<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FuelLog;
use Illuminate\Http\Request;

class FuelController extends Controller
{
    public function index(Request $request)
    {
        $logs = $request->user()->fuelLogs()
            ->with(['vehicle:id,manufacturer,model', 'trip:id,name,distance'])
            ->latest('filled_at')
            ->paginate(20);

        return response()->json($logs);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'trip_id' => 'nullable|exists:trips,id',
            'liters' => 'required|numeric|min:0.1|max:500',
            'cost' => 'nullable|numeric|min:0',
            'odometer_km' => 'nullable|numeric|min:0',
            'fuel_type' => 'nullable|in:petrol,diesel,electric,hybrid',
            'filled_at' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($request->filled('vehicle_id')) {
            $owns = $request->user()->vehicles()->whereKey($request->vehicle_id)->exists();
            if (! $owns) {
                abort(422, 'Vehicle does not belong to you');
            }
        }

        if ($request->filled('trip_id')) {
            $ownsTrip = $request->user()->trips()->whereKey($request->trip_id)->exists();
            if (! $ownsTrip) {
                abort(422, 'Trip does not belong to you');
            }
        }

        $log = $request->user()->fuelLogs()->create([
            ...$validated,
            'filled_at' => $validated['filled_at'] ?? now(),
        ]);

        return response()->json(['message' => 'Fuel log created', 'fuel_log' => $log], 201);
    }

    public function destroy(Request $request, FuelLog $fuelLog)
    {
        if ($fuelLog->user_id !== $request->user()->id) {
            abort(403);
        }

        $fuelLog->delete();

        return response()->json(['message' => 'Fuel log deleted']);
    }

    public function summary(Request $request)
    {
        $user = $request->user();
        $monthStart = now()->startOfMonth();

        $monthLogs = $user->fuelLogs()->where('filled_at', '>=', $monthStart)->get();

        return response()->json([
            'total_liters' => round($monthLogs->sum('liters'), 2),
            'total_cost' => round($monthLogs->sum('cost'), 2),
            'entries_this_month' => $monthLogs->count(),
            'avg_liters_per_fill' => $monthLogs->isNotEmpty()
                ? round($monthLogs->avg('liters'), 2)
                : 0,
        ]);
    }
}
