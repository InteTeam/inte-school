<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\LegalDocumentService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureLegalAcceptance
{
    public function __construct(
        private readonly LegalDocumentService $legalDocumentService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        // Root admins are exempt from school legal acceptance
        if ($user->isRootAdmin()) {
            return $next($request);
        }

        $schoolId = session('current_school_id');

        if ($schoolId === null) {
            return $next($request);
        }

        // Skip the check on the acceptance route itself to avoid redirect loop
        if ($request->routeIs('legal.accept.*')) {
            return $next($request);
        }

        if ($this->legalDocumentService->userNeedsToAccept($user, $schoolId)) {
            return redirect()->route('legal.accept.show');
        }

        return $next($request);
    }
}
