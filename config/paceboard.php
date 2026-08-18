<?php

return [
    'speed_limit_kmh' => (int) env('PACEBOARD_SPEED_LIMIT', 80),
    'ranking_min_score' => (int) env('PACEBOARD_RANKING_MIN_SCORE', 60),
    'maintenance_mode' => (bool) env('PACEBOARD_MAINTENANCE', false),
    'maintenance_message' => env('PACEBOARD_MAINTENANCE_MESSAGE', 'PaceBoard is under maintenance. Please try again later.'),
    'share_base_url' => env('PACEBOARD_SHARE_URL', env('APP_URL', 'http://localhost').'/share'),
    'fuel_consumption_per_100km' => (float) env('PACEBOARD_FUEL_CONSUMPTION', 8.5),

    'fcm' => [
        'enabled' => (bool) env('FCM_ENABLED', false),
        'server_key' => env('FCM_SERVER_KEY'),
        'project_id' => env('FCM_PROJECT_ID'),
    ],

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'africas_talking' => [
            'api_key' => env('AFRICAS_TALKING_API_KEY'),
            'username' => env('AFRICAS_TALKING_USERNAME'),
            'from' => env('AFRICAS_TALKING_FROM', 'PaceBoard'),
        ],
        'twilio' => [
            'sid' => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from' => env('TWILIO_FROM'),
        ],
    ],

    'weather' => [
        'enabled' => (bool) env('WEATHER_ENABLED', false),
        'api_key' => env('OPENWEATHER_API_KEY'),
        'units' => env('WEATHER_UNITS', 'metric'),
    ],

    'deploy' => [
        'enabled' => (bool) env('DEPLOY_HOOK_ENABLED', false),
        'hook_token' => env('DEPLOY_HOOK_TOKEN', ''),
    ],
];
