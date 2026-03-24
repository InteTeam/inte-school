<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskItem;
use App\Models\Scopes\SchoolScope;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    public function index(): InertiaResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $tasks = Task::query()
            ->where(function ($q) use ($user): void {
                $q->where('assignee_id', $user->id)
                    ->orWhere('assigned_by_id', $user->id);
            })
            ->with(['items'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Teacher/Tasks/Index', [
            'tasks' => $tasks,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (! $user->can('create', Task::class)) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'assignee_id' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
        ]);

        $this->taskService->createTask(
            (string) session('current_school_id'),
            $user,
            $validated,
        );

        return redirect()->route('teacher.tasks.index')
            ->with(['alert' => __('tasks.created'), 'type' => 'success']);
    }

    public function createHomework(): InertiaResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $classes = \App\Models\SchoolClass::query()
            ->where('teacher_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'year_group']);

        return Inertia::render('Teacher/Tasks/HomeworkCreate', [
            'classes' => $classes,
        ]);
    }

    public function storeHomework(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (! $user->can('create', Task::class)) {
            abort(403);
        }

        $validated = $request->validate([
            'class_id' => ['required', 'string'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_at' => ['required', 'date', 'after:now'],
        ]);

        $this->taskService->createHomework(
            (string) session('current_school_id'),
            $user,
            $validated['class_id'],
            $validated,
        );

        return redirect()->route('teacher.tasks.index')
            ->with(['alert' => __('tasks.homework_assigned'), 'type' => 'success']);
    }

    public function toggleItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'string'],
        ]);

        $item = TaskItem::withoutGlobalScope(SchoolScope::class)
            ->findOrFail($validated['item_id']);

        $updated = $this->taskService->toggleItem($item);

        return response()->json(['is_completed' => $updated->is_completed]);
    }

    /**
     * Update sort order for drag-reorder.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'task_id' => ['required', 'string'],
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['string'],
        ]);

        $task = Task::withoutGlobalScope(SchoolScope::class)
            ->findOrFail($validated['task_id']);

        $this->taskService->reorder($task, $validated['ordered_ids']);

        return response()->json(['ok' => true]);
    }
}
