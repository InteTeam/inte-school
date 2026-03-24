<?php

declare(strict_types=1);

namespace App\Http\Controllers\RootAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use Inertia\Inertia;
use Inertia\Response;

final class SchoolController extends Controller
{
    public function index(): Response
    {
        $schools = School::withTrashed()
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'slug', 'plan', 'is_active', 'rag_enabled', 'created_at', 'deleted_at']);

        return Inertia::render('RootAdmin/Schools/Index', [
            'schools' => $schools,
        ]);
    }
}
