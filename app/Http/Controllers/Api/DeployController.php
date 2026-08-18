<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DeployRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class DeployController extends Controller
{
    public function run(Request $request, DeployRunner $deployRunner): JsonResponse
    {
        if (! $this->hookAuthorized($request)) {
            return response()->json(['message' => 'Unauthorized deploy hook'], 401);
        }

        if (! $this->hookEnabled()) {
            return response()->json(['message' => 'Deploy hook is disabled'], 403);
        }

        try {
            return response()->json($deployRunner->run());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    private function hookEnabled(): bool
    {
        return filter_var(
            $this->envValue('DEPLOY_HOOK_ENABLED') ?? config('paceboard.deploy.enabled', false),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    private function hookAuthorized(Request $request): bool
    {
        $expected = (string) ($this->envValue('DEPLOY_HOOK_TOKEN') ?? config('paceboard.deploy.hook_token', ''));
        $provided = (string) $request->header('X-Deploy-Token', '');

        return $expected !== '' && hash_equals($expected, $provided);
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
