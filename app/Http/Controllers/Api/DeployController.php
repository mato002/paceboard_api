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
        $enabled = filter_var($this->envValue('DEPLOY_HOOK_ENABLED') ?? config('paceboard.deploy.enabled', false), FILTER_VALIDATE_BOOLEAN);
        $expectedToken = (string) ($this->envValue('DEPLOY_HOOK_TOKEN') ?? config('paceboard.deploy.hook_token', ''));
        $providedToken = (string) $request->header('X-Deploy-Token', '');

        if (! $enabled) {
            return response()->json(['message' => 'Deploy hook is disabled'], 403);
        }

        if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            return response()->json(['message' => 'Unauthorized deploy hook'], 401);
        }

        $commands = [
            'optimize:clear',
            'migrate --force',
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

    private function envValue(string $key): ?string
    {
        $file = base_path('.env');
        if (! is_file($file)) {
            return null;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_starts_with($line, $key.'=')) {
                continue;
            }

            $value = trim(substr($line, strlen($key) + 1));
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"'))
                || (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            return $value;
        }

        return null;
    }
}
