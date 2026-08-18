<?php

/**
 * Shared-hosting deploy hook. Lives in the web document root so GitHub
 * Actions can call it without SSH or Laravel /api routing.
 */
header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
    exit;
}

$autoload = null;
foreach ([
    __DIR__.'/../vendor/autoload.php',
    __DIR__.'/../../paceboard/vendor/autoload.php',
    '/home/zhenhlkl/paceboard/vendor/autoload.php',
] as $path) {
    if (is_file($path)) {
        $autoload = $path;
        break;
    }
}

if ($autoload === null) {
    http_response_code(500);
    echo json_encode(['message' => 'Laravel autoload not found']);
    exit;
}

require $autoload;

$appRoot = dirname($autoload, 2);
$app = require $appRoot.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$envValue = static function (string $root, string $key): ?string {
    $file = $root.'/.env';
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
};

$enabled = filter_var(
    $envValue($appRoot, 'DEPLOY_HOOK_ENABLED') ?? config('paceboard.deploy.enabled', false),
    FILTER_VALIDATE_BOOLEAN
);
$expected = (string) ($envValue($appRoot, 'DEPLOY_HOOK_TOKEN') ?? config('paceboard.deploy.hook_token', ''));
$provided = (string) ($_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? '');

if (! $enabled) {
    http_response_code(403);
    echo json_encode(['message' => 'Deploy hook is disabled']);
    exit;
}

if ($expected === '' || ! hash_equals($expected, $provided)) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized deploy hook']);
    exit;
}

$commands = [
    'optimize:clear',
    'migrate --force',
    'config:cache',
    'route:cache',
];

$results = [];
foreach ($commands as $command) {
    $exitCode = Illuminate\Support\Facades\Artisan::call($command);
    $results[] = [
        'command' => $command,
        'exit_code' => $exitCode,
        'output' => trim(Illuminate\Support\Facades\Artisan::output()),
    ];

    if ($exitCode !== 0) {
        http_response_code(500);
        echo json_encode([
            'message' => 'Deployment hook command failed',
            'results' => $results,
        ]);
        exit;
    }
}

echo json_encode([
    'message' => 'Deployment tasks completed',
    'results' => $results,
]);
