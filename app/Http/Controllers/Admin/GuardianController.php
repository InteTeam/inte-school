<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuardianController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
    ) {}

    public function index(): Response
    {
        $schoolId = session('current_school_id');

        /** @var School $school */
        $school = School::find($schoolId);

        $guardians = $school->usersWithRole('parent')
            ->orderBy('users.name')
            ->get()
            ->map(function (\App\Models\User $u): array {
                /** @var object{accepted_at: string|null} $pivot */
                // @phpstan-ignore-next-line property.notFound
                $pivot = $u->pivot;

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'accepted_at' => $pivot->accepted_at,
                ];
            });

        return Inertia::render('Admin/Guardians/Index', [
            'guardians' => $guardians,
        ]);
    }

    public function generateCode(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => ['required', 'string'],
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $schoolId = session('current_school_id');

        /** @var School $school */
        $school = School::find($schoolId);

        $code = $this->userManagementService->generateGuardianInviteCode(
            $school,
            $request->string('student_id')->toString(),
            $user
        );

        return response()->json(['code' => $code]);
    }

    public function link(Request $request): JsonResponse
    {
        $request->validate([
            'guardian_id' => ['required', 'string'],
            'student_id' => ['required', 'string'],
            'is_primary' => ['boolean'],
        ]);

        $schoolId = session('current_school_id');

        /** @var School $school */
        $school = School::find($schoolId);

        $link = $this->userManagementService->linkGuardianToStudent(
            $school,
            $request->string('guardian_id')->toString(),
            $request->string('student_id')->toString(),
            (bool) $request->boolean('is_primary', true),
        );

        return response()->json(['id' => $link->id]);
    }
}
