<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class FeatureGate
{
    public function handle(Request $request, Closure $next, string ...$features): Response
    {
        $schoolId = $request->session()->get('current_school_id');

        if (! $schoolId) {
            abort(403, __('feature.no_school_context'));
        }

        $school = Cache::remember(
            "school:{$schoolId}:features",
            3600,
            fn () => School::find($schoolId)
        );

        if (! $school) {
            abort(403, __('feature.school_not_found'));
        }

        foreach ($features as $feature) {
            if (! $school->isFeatureEnabled($feature)) {
                abort(403, __('feature.not_enabled', ['feature' => $feature]));
            }
        }

        return $next($request);
    }
}
