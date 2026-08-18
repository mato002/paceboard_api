<?php

/**
 * Streams gallery/trip photos without Laravel route cache.
 * Lives in the web document root (public_html/paceboard) like deploy-hook.php.
 */
$id = (int) ($_GET['id'] ?? 0);
$rawPath = (string) ($_GET['path'] ?? '');

if ($id < 1 && $rawPath === '') {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Photo not found']);
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
    header('Content-Type: application/json');
    echo json_encode(['message' => 'App not found']);
    exit;
}

require $autoload;

$appRoot = dirname($autoload, 2);
$app = require $appRoot.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$disk = Illuminate\Support\Facades\Storage::disk('public');
$filePath = null;

if ($id > 0) {
    $photo = App\Models\TripPhoto::query()->find($id);
    $filePath = $photo?->path;
} else {
    $filePath = str_replace('\\', '/', $rawPath);
    $filePath = ltrim($filePath, '/');
    if (str_starts_with($filePath, 'storage/')) {
        $filePath = substr($filePath, strlen('storage/'));
    }
    if ($filePath === '' || str_contains($filePath, '..')) {
        $filePath = null;
    }
}

if ($filePath === null || ! $disk->exists($filePath)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Photo not found']);
    exit;
}

$disk->response($filePath, null, [
    'Cache-Control' => 'public, max-age=604800',
])->send();
