<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$cachedRoutes = __DIR__.'/cache/routes-v7.php';
if (is_file($cachedRoutes) && ! str_contains((string) file_get_contents($cachedRoutes), 'api/media/photos')) {
    @unlink($cachedRoutes);
    @unlink(__DIR__.'/cache/routes.php');
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'maintenance' => \App\Http\Middleware\CheckAppMaintenance::class,
            'permission' => \App\Http\Middleware\EnsureUserHasPermission::class,
        ]);

        $middleware->throttleApi('api');

        $middleware->validateCsrfTokens(except: [
            'internal/deploy',
            'deploy-hook.php',
        ]);

        $middleware->redirectGuestsTo('/admin/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
