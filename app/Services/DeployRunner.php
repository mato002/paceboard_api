<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class DeployRunner
{
    /**
     * Post-deploy tasks run automatically after GitHub pushes code via FTP.
     * Order matters: clear caches, migrate DB, then rebuild caches.
     *
     * @return array{message: string, results: list<array{command: string, exit_code: int, output: string}>, migrations_applied: bool}
     */
    public function run(): array
    {
        $commands = [
            'optimize:clear',
            'migrate --force --no-interaction',
            'config:cache',
            'route:cache',
        ];

        $results = [];
        $migrationsApplied = false;

        foreach ($commands as $command) {
            $exitCode = Artisan::call($command);
            $output = trim(Artisan::output());

            $results[] = [
                'command' => $command,
                'exit_code' => $exitCode,
                'output' => $output,
            ];

            if ($command === 'migrate --force --no-interaction' && $exitCode === 0) {
                $migrationsApplied = true;
            }

            if ($exitCode !== 0) {
                throw new RuntimeException("Deployment command failed: {$command}");
            }
        }

        return [
            'message' => 'Deployment tasks completed',
            'results' => $results,
            'migrations_applied' => $migrationsApplied,
        ];
    }
}
