<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Throwable;

class DatabaseBootstrapper
{
    public static function ensureReady(): void
    {
        if (! self::enabled()) {
            return;
        }

        if (app()->runningInConsole() || app()->environment('testing')) {
            return;
        }

        if (self::signature() === self::readSentinel()) {
            return;
        }

        try {
            self::run();
        } catch (Throwable $e) {
            report($e);
        }
    }

    public static function run(): string
    {
        $lockPath = storage_path('framework/migrations.lock');
        $lock = fopen($lockPath, 'c');

        if ($lock === false) {
            throw new RuntimeException('Unable to create a migration lock file. Check that storage/framework is writable.');
        }

        if (! flock($lock, LOCK_EX)) {
            fclose($lock);

            throw new RuntimeException('Unable to acquire the migration lock.');
        }

        $output = '';

        try {
            if (self::signature() === self::readSentinel()) {
                return 'Migrations are already up to date.';
            }

            Artisan::call('migrate', ['--force' => true]);
            $output .= Artisan::output();

            file_put_contents(storage_path('framework/migrations.complete'), self::signature());
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        return trim($output) === '' ? 'Migrations are already up to date.' : $output;
    }

    private static function enabled(): bool
    {
        return filter_var(config('paceboard.auto_migrate', true), FILTER_VALIDATE_BOOLEAN);
    }

    private static function signature(): string
    {
        $files = glob(database_path('migrations/*.php')) ?: [];
        sort($files);

        return hash('sha256', implode('|', array_map('basename', $files)));
    }

    private static function readSentinel(): string
    {
        $path = storage_path('framework/migrations.complete');

        return is_file($path) ? trim((string) file_get_contents($path)) : '';
    }
}
