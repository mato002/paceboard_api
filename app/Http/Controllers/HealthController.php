<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'storage' => $this->checkStorage(),
            'cache' => $this->checkCache(),
        ];

        $healthy = collect($checks)->every(fn (array $check) => $check['ok']);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'app' => config('app.name'),
            'environment' => config('app.env'),
            'time' => now()->toIso8601String(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['ok' => true, 'message' => 'Connected'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Database unreachable'];
        }
    }

    private function checkStorage(): array
    {
        $path = storage_path('framework');
        $writable = is_dir($path) && is_writable($path);

        return [
            'ok' => $writable,
            'message' => $writable ? 'Writable' : 'Storage not writable',
        ];
    }

    private function checkCache(): array
    {
        try {
            Cache::put('health_check', true, 10);

            return [
                'ok' => Cache::get('health_check') === true,
                'message' => 'Operational',
            ];
        } catch (\Throwable) {
            return ['ok' => false, 'message' => 'Cache unavailable'];
        }
    }
}
