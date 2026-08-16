<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    public function fetch(float $lat, float $lng): ?array
    {
        if (! config('paceboard.weather.enabled')) {
            return null;
        }

        $apiKey = config('paceboard.weather.api_key');
        if (! $apiKey) {
            return null;
        }

        $response = Http::get('https://api.openweathermap.org/data/2.5/weather', [
            'lat' => $lat,
            'lon' => $lng,
            'appid' => $apiKey,
            'units' => config('paceboard.weather.units', 'metric'),
        ]);

        if (! $response->successful()) {
            Log::warning('Weather API failed', ['body' => $response->body()]);

            return null;
        }

        $data = $response->json();

        return [
            'condition' => $data['weather'][0]['main'] ?? null,
            'description' => $data['weather'][0]['description'] ?? null,
            'temperature_c' => $data['main']['temp'] ?? null,
            'humidity' => $data['main']['humidity'] ?? null,
            'wind_speed' => $data['wind']['speed'] ?? null,
            'icon' => $data['weather'][0]['icon'] ?? null,
            'fetched_at' => now()->toIso8601String(),
        ];
    }
}
