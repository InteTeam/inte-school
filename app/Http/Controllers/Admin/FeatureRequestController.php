<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\FeatureRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class FeatureRequestController extends Controller
{
    public function __construct(
        private readonly FeatureRequestService $featureRequestService,
    ) {}

    public function index(): InertiaResponse
    {
        $school = $this->currentSchool();
        $requests = $this->featureRequestService->listForSchool($school);

        return Inertia::render('Admin/Settings/FeatureRequests', [
            'requests' => $requests,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $this->featureRequestService->submit(
            $this->currentSchool(),
            $user,
            $validated['title'],
            $validated['body'],
        );

        return redirect()->route('admin.settings.feature-requests')
            ->with(['alert' => __('feature_requests.submitted'), 'type' => 'success']);
    }

    private function currentSchool(): School
    {
        /** @var School $school */
        $school = School::find(session('current_school_id'));

        return $school;
    }
}
