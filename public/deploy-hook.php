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

$enabled = (bool) config('paceboard.deploy.enabled', false);
$expected = (string) config('paceboard.deploy.hook_token', '');
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
    'migrate --force',
    'optimize:clear',
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
