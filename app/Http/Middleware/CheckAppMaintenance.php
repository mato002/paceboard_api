<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAppMaintenance
{
    public function __construct(private SettingsService $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->isMaintenanceMode()) {
            return $next($request);
        }

        if ($request->user()?->is_admin) {
            return $next($request);
        }

        if ($request->is('api/admin/*') || $request->is('admin/*')) {
            return $next($request);
        }

        return response()->json([
            'message' => $this->settings->maintenanceMessage(),
            'maintenance_mode' => true,
        ], 503);
    }
}
