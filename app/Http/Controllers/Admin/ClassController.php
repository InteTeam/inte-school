<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClassRequest;
use App\Models\School;
use App\Models\SchoolClass;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ClassController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
    ) {}

    public function index(): Response
    {
        $schoolId = session('current_school_id');

        /** @var School $school */
        $school = School::find($schoolId);

        $classes = SchoolClass::query()
            ->with('teacher:id,name,email')
            ->withCount('students')
            ->orderBy('year_group')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'year_group' => $c->year_group,
                'teacher' => $c->teacher ? ['id' => $c->teacher->id, 'name' => $c->teacher->name] : null,
                'students_count' => $c->students_count,
            ]);

        return Inertia::render('Admin/Classes/Index', [
            'classes' => $classes,
        ]);
    }

    public function show(SchoolClass $class): Response
    {
        $class->load(['teacher:id,name,email', 'students:id,name,email']);

        return Inertia::render('Admin/Classes/Show', [
            'class' => [
                'id' => $class->id,
                'name' => $class->name,
                'year_group' => $class->year_group,
                'teacher' => $class->teacher ? ['id' => $class->teacher->id, 'name' => $class->teacher->name] : null,
                'students' => $class->students->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'email' => $s->email]),
            ],
        ]);
    }

    public function store(StoreClassRequest $request): RedirectResponse
    {
        SchoolClass::create($request->validated());

        return redirect()->route('admin.classes.index')
            ->with(['alert' => __('classes.created'), 'type' => 'success']);
    }

    public function update(StoreClassRequest $request, SchoolClass $class): RedirectResponse
    {
        $class->update($request->validated());

        return redirect()->route('admin.classes.show', $class->id)
            ->with(['alert' => __('classes.updated'), 'type' => 'success']);
    }

    public function destroy(SchoolClass $class): RedirectResponse
    {
        $class->delete();

        return redirect()->route('admin.classes.index')
            ->with(['alert' => __('classes.deleted'), 'type' => 'success']);
    }

    public function addStudent(SchoolClass $class, string $studentId): RedirectResponse
    {
        $schoolId = session('current_school_id');

        /** @var School $school */
        $school = School::find($schoolId);

        $this->userManagementService->addStudentToClass($school, $class->id, $studentId);

        return redirect()->route('admin.classes.show', $class->id)
            ->with(['alert' => __('classes.student_added'), 'type' => 'success']);
    }

    public function removeStudent(SchoolClass $class, string $studentId): RedirectResponse
    {
        $this->userManagementService->removeStudentFromClass($class->id, $studentId);

        return redirect()->route('admin.classes.show', $class->id)
            ->with(['alert' => __('classes.student_removed'), 'type' => 'success']);
    }
}
