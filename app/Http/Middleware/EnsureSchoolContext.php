<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSchoolContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $schoolId = session('current_school_id');

        if ($schoolId === null) {
            // No school context — SchoolScope will return empty results.
            // School selection is handled in P1.5 onboarding/dashboard flow.
            return $next($request);
        }

        $school = Cache::remember(
            "school:{$schoolId}:settings",
            3600,
            fn () => School::find($schoolId)
        );

        if ($school === null || ! $school->isActive()) {
            $request->session()->forget('current_school_id');

            return redirect()->route('login')
                ->withErrors(['school' => __('school.inactive_or_not_found')]);
        }

        // Root admins bypass school membership check
        if ($user->isRootAdmin()) {
            return $next($request);
        }

        $isMember = $school->users()
            ->where('users.id', $user->id)
            ->wherePivotNotNull('accepted_at')
            ->exists();

        if (! $isMember) {
            $request->session()->forget('current_school_id');

            return redirect()->route('login')
                ->withErrors(['school' => __('school.not_a_member')]);
        }

        return $next($request);
    }
}
