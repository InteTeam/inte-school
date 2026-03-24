<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRegister;
use App\Models\School;
use App\Models\Scopes\SchoolScope;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
    ) {}

    public function index(): InertiaResponse
    {
        $school = $this->currentSchool();

        $stats = $this->attendanceService->getDailyStats($school, now());

        $registers = AttendanceRegister::query()
            ->whereDate('register_date', now()->toDateString())
            ->with(['schoolClass', 'teacher'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/Attendance/Index', [
            'stats' => $stats,
            'registers' => $registers,
        ]);
    }

    /**
     * Admin override: mark any student in any class.
     */
    public function override(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'register_id' => ['required', 'string'],
            'student_id' => ['required', 'string'],
            'status' => ['required', 'string', 'in:present,absent,late'],
            'notes' => ['nullable', 'string', 'max:500'],
            'pre_notified' => ['boolean'],
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $register = AttendanceRegister::withoutGlobalScope(SchoolScope::class)
            ->findOrFail($validated['register_id']);

        if (! $user->can('mark', $register)) {
            abort(403);
        }

        $this->attendanceService->mark(
            $register,
            $validated['student_id'],
            $validated['status'],
            $user,
            'manual',
            $validated['notes'] ?? null,
            (bool) ($validated['pre_notified'] ?? false),
        );

        return redirect()->back()
            ->with(['alert' => __('attendance.marked'), 'type' => 'success']);
    }

    private function currentSchool(): School
    {
        /** @var School $school */
        $school = School::find(session('current_school_id'));

        return $school;
    }
}
