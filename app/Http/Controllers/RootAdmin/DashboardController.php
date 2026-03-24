<?php

declare(strict_types=1);

namespace App\Http\Controllers\RootAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('RootAdmin/Dashboard', [
            'stats' => [
                'school_count' => School::withTrashed()->count(),
                'active_school_count' => School::where('is_active', true)->count(),
                'user_count' => User::count(),
            ],
        ]);
    }
}
