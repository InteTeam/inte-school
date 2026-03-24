<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskTemplateGroup;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    public function templateGroupsIndex(): InertiaResponse
    {
        $groups = TaskTemplateGroup::query()
            ->with(['templates'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/Tasks/TemplateGroups/Index', [
            'groups' => $groups,
        ]);
    }

    public function storeTemplateGroup(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'department_label' => ['nullable', 'string', 'max:100'],
            'task_type' => ['required', 'string', 'in:staff'],
            'templates' => ['nullable', 'array'],
            'templates.*.name' => ['required', 'string', 'max:200'],
            'templates.*.sort_order' => ['integer'],
            'templates.*.default_deadline_hours' => ['nullable', 'integer', 'min:1'],
        ]);

        $schoolId = (string) session('current_school_id');

        $group = \App\Models\TaskTemplateGroup::forceCreate([
            'school_id' => $schoolId,
            'name' => $validated['name'],
            'department_label' => $validated['department_label'] ?? null,
            'task_type' => $validated['task_type'],
        ]);

        foreach ($validated['templates'] ?? [] as $index => $tpl) {
            \App\Models\TaskTemplate::forceCreate([
                'school_id' => $schoolId,
                'group_id' => $group->id,
                'name' => $tpl['name'],
                'sort_order' => $tpl['sort_order'] ?? $index,
                'default_deadline_hours' => $tpl['default_deadline_hours'] ?? null,
            ]);
        }

        return redirect()->route('admin.tasks.template-groups.index')
            ->with(['alert' => __('tasks.template_group_created'), 'type' => 'success']);
    }

    public function applyTemplate(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'task_id' => ['required', 'string'],
            'template_group_id' => ['required', 'string'],
        ]);

        $task = Task::withoutGlobalScope(\App\Models\Scopes\SchoolScope::class)
            ->findOrFail($validated['task_id']);

        $this->taskService->applyTemplateGroup($task, $validated['template_group_id']);

        return redirect()->back()
            ->with(['alert' => __('tasks.template_applied'), 'type' => 'success']);
    }
}
