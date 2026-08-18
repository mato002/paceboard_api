<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DeployController extends Controller
{
    public function run(Request $request): JsonResponse
    {
        $enabled = (bool) config('paceboard.deploy.enabled', false);
        $expectedToken = (string) config('paceboard.deploy.hook_token', '');
        $providedToken = (string) $request->header('X-Deploy-Token', '');

        if (! $enabled) {
            return response()->json(['message' => 'Deploy hook is disabled'], 403);
        }

        if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            return response()->json(['message' => 'Unauthorized deploy hook'], 401);
        }

        $commands = [
            'migrate --force',
            'optimize:clear',
            'config:cache',
            'route:cache',
        ];

        $results = [];
        foreach ($commands as $command) {
            $exitCode = Artisan::call($command);
            $results[] = [
                'command' => $command,
                'exit_code' => $exitCode,
                'output' => trim(Artisan::output()),
            ];

            if ($exitCode !== 0) {
                return response()->json([
                    'message' => 'Deployment hook command failed',
                    'results' => $results,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Deployment tasks completed',
            'results' => $results,
        ]);
    }
}
