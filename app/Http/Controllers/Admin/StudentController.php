<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EnrolStudentRequest;
use App\Http\Requests\Admin\ImportStudentsRequest;
use App\Jobs\ProcessStudentCsvImportJob;
use App\Models\School;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
    ) {}

    public function index(): Response
    {
        $schoolId = session('current_school_id');

        /** @var School $school */
        $school = School::find($schoolId);

        $students = $school->usersWithRole('student')
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

        return Inertia::render('Admin/Students/Index', [
            'students' => $students,
        ]);
    }

    public function enrol(EnrolStudentRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $schoolId = session('current_school_id');

        /** @var School $school */
        $school = School::find($schoolId);

        $this->userManagementService->enrolStudent($school, $request->validated(), $user);

        return redirect()->route('admin.students.index')
            ->with(['alert' => __('students.enrolled'), 'type' => 'success']);
    }

    public function import(ImportStudentsRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $schoolId = session('current_school_id');

        /** @var School $school */
        $school = School::find($schoolId);

        $handle = fopen($request->file('csv')->getRealPath(), 'r');

        if ($handle === false) {
            return redirect()->back()
                ->with(['alert' => __('students.import_failed'), 'type' => 'error']);
        }

        $rows = $this->userManagementService->parseCsvImport($handle);
        fclose($handle);

        ProcessStudentCsvImportJob::dispatch($school, $user, $rows);

        return redirect()->route('admin.students.index')
            ->with(['alert' => __('students.import_queued'), 'type' => 'success']);
    }

    public function exportTemplate(): HttpResponse
    {
        $template = $this->userManagementService->csvImportTemplate();

        $csv = implode(',', $template[0]) . "\n";
        $csv .= "Jane Smith,jane.smith@example.com,Year 3,3A\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="student_import_template.csv"',
        ]);
    }
}
