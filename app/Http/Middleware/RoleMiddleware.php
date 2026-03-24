<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        // Root admins can access any role route
        if ($user->isRootAdmin()) {
            return $next($request);
        }

        if (! $user->hasRoleInCurrentSchool($roles)) {
            abort(403, __('role.forbidden'));
        }

        return $next($request);
    }
}
