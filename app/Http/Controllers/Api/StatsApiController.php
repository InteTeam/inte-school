<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolApiKey;
use App\Services\StatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * External statistics API — authenticated by API key hash.
 * Returns only data covered by the key's permissions JSONB.
 */
class StatsApiController extends Controller
{
    public function __construct(
        private readonly StatisticsService $statistics,
    ) {}

    public function index(Request $request, string $schoolSlug): JsonResponse
    {
        /** @var SchoolApiKey $apiKey */
        $apiKey = $request->attributes->get('api_key');

        $school = School::withoutGlobalScope(\App\Models\Scopes\SchoolScope::class)
            ->where('slug', $schoolSlug)
            ->where('id', $apiKey->school_id) // ensure key matches the requested school
            ->first();

        if ($school === null) {
            return response()->json(['error' => __('api.school_not_found')], 404);
        }

        $period = $request->get('period', 'week');
        if (! in_array($period, ['week', 'month', 'term'], true)) {
            $period = 'week';
        }

        $data = $this->statistics->getForApi($school, (array) $apiKey->permissions, $period);

        return response()->json($data);
    }
}
