<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\StatisticsService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class StatisticsController extends Controller
{
    public function __construct(
        private readonly StatisticsService $statistics,
    ) {}

    public function index(): InertiaResponse
    {
        $school = $this->currentSchool();
        $period = request()->get('period', 'week');

        if (! in_array($period, ['week', 'month', 'term'], true)) {
            $period = 'week';
        }

        $stats = $this->statistics->getDashboard($school, $period);

        return Inertia::render('Admin/Statistics/Dashboard', [
            'stats' => $stats,
            'period' => $period,
        ]);
    }

    private function currentSchool(): School
    {
        /** @var School $school */
        $school = School::find(session('current_school_id'));

        return $school;
    }
}
