<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InviteStaffRequest;
use App\Models\School;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
    ) {}

    public function index(): Response
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $schoolId = session('current_school_id');

        /** @var School $school */
        $school = School::find($schoolId);

        $staff = $school->users()
            ->wherePivotIn('role', ['admin', 'teacher', 'support'])
            ->orderBy('users.name')
            ->get()
            ->map(function (\App\Models\User $u): array {
                /** @var object{role: string, department_label: string|null, accepted_at: string|null, invited_at: string} $pivot */
                // @phpstan-ignore-next-line property.notFound
                $pivot = $u->pivot;

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $pivot->role,
                    'department_label' => $pivot->department_label,
                    'accepted_at' => $pivot->accepted_at,
                    'invited_at' => $pivot->invited_at,
                ];
            });

        return Inertia::render('Admin/Staff/Index', [
            'staff' => $staff,
        ]);
    }

    public function invite(InviteStaffRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $schoolId = session('current_school_id');

        /** @var School $school */
        $school = School::find($schoolId);

        $this->userManagementService->inviteStaff($school, $request->validated(), $user);

        return redirect()->route('admin.staff.index')
            ->with(['alert' => __('staff.invited'), 'type' => 'success']);
    }
}
