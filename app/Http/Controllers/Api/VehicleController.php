<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $vehicles = $request->user()->vehicles()->latest()->get()
            ->map(fn (Vehicle $vehicle) => $vehicle->toApiArray());

        return response()->json($vehicles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'manufacturer' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'year' => 'nullable|integer',
            'color' => 'nullable|string|max:50',
            'registration_number' => 'nullable|string|max:50',
            'fuel_type' => 'nullable|string|max:50',
        ]);

        $vehicle = $request->user()->vehicles()->create($validated);

        return response()->json($vehicle->toApiArray(), 201);
    }

    public function serviceStatus(Request $request, Vehicle $vehicle)
    {
        if ($vehicle->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json([
            'vehicle_id' => $vehicle->id,
            ...$vehicle->serviceSummary(),
            'last_service_at' => $vehicle->last_service_at,
        ]);
    }

    public function recordService(Request $request, Vehicle $vehicle)
    {
        if ($vehicle->user_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate([
            'odometer_km' => 'required|integer|min:0',
        ]);

        $vehicle->update([
            'last_service_odometer_km' => $request->odometer_km,
            'last_service_at' => now(),
            'mileage' => $request->odometer_km,
        ]);

        return response()->json([
            'message' => 'Service recorded',
            'vehicle' => $vehicle->fresh()->toApiArray(),
        ]);
    }

    public function show(Request $request, Vehicle $vehicle)
    {
        if ($vehicle->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($vehicle->toApiArray());
    }

    public function destroy(Request $request, Vehicle $vehicle)
    {
        if ($vehicle->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $vehicle->delete();

        return response()->json(['message' => 'Vehicle deleted']);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        if ($vehicle->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'manufacturer' => 'sometimes|string|max:255',
            'model' => 'sometimes|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'year' => 'nullable|integer',
            'color' => 'nullable|string|max:50',
            'registration_number' => 'nullable|string|max:50',
            'fuel_type' => 'nullable|string|max:50',
        ]);

        $vehicle->update($validated);

        return response()->json($vehicle->fresh()->toApiArray());
    }
}
