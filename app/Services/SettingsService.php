<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    private const CACHE_KEY = 'paceboard.settings.all';

    private const CACHE_TTL = 300;

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->cachedAll();

        return $all[$key] ?? $default;
    }

    public function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        AppSetting::setValue($key, $value, $type, $group);
        Cache::forget(self::CACHE_KEY);
    }

    public function all(): array
    {
        return $this->cachedAll();
    }

    public function isMaintenanceMode(): bool
    {
        return (bool) $this->get('maintenance_mode', config('paceboard.maintenance_mode'));
    }

    public function maintenanceMessage(): string
    {
        return (string) $this->get('maintenance_message', config('paceboard.maintenance_message'));
    }

    private function cachedAll(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return [
                'speed_limit_kmh' => AppSetting::getValue('speed_limit_kmh', config('paceboard.speed_limit_kmh')),
                'ranking_min_score' => AppSetting::getValue('ranking_min_score', config('paceboard.ranking_min_score')),
                'maintenance_mode' => AppSetting::getValue('maintenance_mode', config('paceboard.maintenance_mode')),
                'maintenance_message' => AppSetting::getValue('maintenance_message', config('paceboard.maintenance_message')),
                'fuel_consumption_per_100km' => AppSetting::getValue('fuel_consumption_per_100km', config('paceboard.fuel_consumption_per_100km')),
                'map_provider' => AppSetting::getValue('map_provider', 'openstreetmap'),
                'ranking_rules' => AppSetting::getValue('ranking_rules', ['primary' => 'safety', 'min_trips' => 3], 'json'),
            ];
        });
    }
}
