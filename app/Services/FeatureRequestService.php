<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FeatureRequest;
use App\Models\School;
use App\Models\Scopes\SchoolScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Manages feature request submission and listing.
 * Root admin bypasses school scope to read all requests.
 */
final class FeatureRequestService
{
    private const MAX_BODY_LENGTH = 2000;

    /**
     * Submit a new feature request from a school admin.
     */
    public function submit(School $school, User $submitter, string $title, string $body): FeatureRequest
    {
        // Enforce 2000-char limit at service layer (belt-and-suspenders over form validation)
        $body = mb_substr($body, 0, self::MAX_BODY_LENGTH);

        return FeatureRequest::forceCreate([
            'school_id' => $school->id,
            'submitted_by' => $submitter->id,
            'title' => $title,
            'body' => $body,
            'status' => 'open',
        ]);
    }

    /**
     * List all requests for a single school, newest first.
     *
     * @return Collection<int, FeatureRequest>
     */
    public function listForSchool(School $school): Collection
    {
        return FeatureRequest::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $school->id)
            ->orderBy('created_at', 'desc')
            ->with('submitter:id,name')
            ->get();
    }

    /**
     * List all requests across all schools — root admin only.
     *
     * @return Collection<int, FeatureRequest>
     */
    public function listAll(): Collection
    {
        return FeatureRequest::withoutGlobalScope(SchoolScope::class)
            ->orderBy('created_at', 'desc')
            ->with(['submitter:id,name', 'school:id,name,slug'])
            ->get();
    }

    /**
     * Update request status — root admin action.
     */
    public function updateStatus(FeatureRequest $request, string $status): FeatureRequest
    {
        $request->forceFill(['status' => $status])->save();

        return $request;
    }
}
