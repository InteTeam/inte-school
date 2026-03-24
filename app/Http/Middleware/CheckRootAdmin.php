<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CheckRootAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            abort(403, 'Authentication required.');
        }

        if (! $request->user()->isRootAdmin()) {
            abort(403, 'Root admin access required.');
        }

        return $next($request);
    }
}
