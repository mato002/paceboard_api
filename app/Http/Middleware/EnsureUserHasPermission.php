<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->is_admin || $user->hasPermission($permission)) {
            return $next($request);
        }

        abort(403, 'Insufficient permissions');
    }
}
