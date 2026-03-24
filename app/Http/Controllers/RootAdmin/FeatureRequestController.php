<?php

declare(strict_types=1);

namespace App\Http\Controllers\RootAdmin;

use App\Http\Controllers\Controller;
use App\Models\FeatureRequest;
use App\Models\Scopes\SchoolScope;
use App\Services\FeatureRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class FeatureRequestController extends Controller
{
    public function __construct(
        private readonly FeatureRequestService $featureRequestService,
    ) {}

    /**
     * Cross-school feed — all requests ordered newest first.
     */
    public function index(): InertiaResponse
    {
        $requests = $this->featureRequestService->listAll();

        return Inertia::render('RootAdmin/FeatureRequests/Index', [
            'requests' => $requests,
        ]);
    }

    /**
     * Update the status of a feature request.
     * Uses explicit scope bypass — root admin has no school session context.
     */
    public function updateStatus(Request $request, string $featureRequestId): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:open,under_review,planned,done,declined'],
        ]);

        $featureRequest = FeatureRequest::withoutGlobalScope(SchoolScope::class)
            ->findOrFail($featureRequestId);

        $this->featureRequestService->updateStatus($featureRequest, $validated['status']);

        return redirect()->route('root-admin.feature-requests.index')
            ->with(['alert' => __('feature_requests.status_updated'), 'type' => 'success']);
    }
}
