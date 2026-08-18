<?php

namespace App\Services;

use App\Support\DatabaseBootstrapper;
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
        ];

        $results = [];
        $migrationsApplied = false;

        foreach ($commands as $command) {
            if ($command === 'migrate --force --no-interaction') {
                try {
                    $output = DatabaseBootstrapper::run();
                    $exitCode = 0;
                    $migrationsApplied = true;
                } catch (RuntimeException $e) {
                    $output = $e->getMessage();
                    $exitCode = 1;
                }

                $results[] = [
                    'command' => $command,
                    'exit_code' => $exitCode,
                    'output' => $output,
                ];

                if ($exitCode !== 0) {
                    throw new RuntimeException("Deployment command failed: {$command}");
                }

                continue;
            }

            $exitCode = Artisan::call($command);
            $output = trim(Artisan::output());

            $results[] = [
                'command' => $command,
                'exit_code' => $exitCode,
                'output' => $output,
            ];

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
