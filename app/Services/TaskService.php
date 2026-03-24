<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Scopes\SchoolScope;
use App\Models\Task;
use App\Models\TaskItem;
use App\Models\TaskTemplate;
use App\Models\TaskTemplateGroup;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class TaskService
{
    /**
     * Create a staff task or action item.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTask(string $schoolId, User $creator, array $data): Task
    {
        /** @var Task $task */
        $task = Task::forceCreate([
            'school_id' => $schoolId,
            'type' => $data['type'] ?? 'staff_task',
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => 'todo',
            'priority' => $data['priority'] ?? null,
            'assignee_id' => $data['assignee_id'] ?? null,
            'assigned_by_id' => $creator->id,
            'department_label' => $data['department_label'] ?? null,
            'class_id' => $data['class_id'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'source_message_id' => $data['source_message_id'] ?? null,
        ]);

        return $task;
    }

    /**
     * Create a homework task assigned to a class (one task per class).
     *
     * @param  array<string, mixed>  $data
     */
    public function createHomework(string $schoolId, User $teacher, string $classId, array $data): Task
    {
        return $this->createTask($schoolId, $teacher, [
            'type' => 'homework',
            'class_id' => $classId,
            ...$data,
        ]);
    }

    /**
     * Create an action item linked to a message transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function createActionItem(string $schoolId, User $creator, string $messageId, array $data): Task
    {
        return $this->createTask($schoolId, $creator, [
            'type' => 'action_item',
            'source_message_id' => $messageId,
            ...$data,
        ]);
    }

    /**
     * Apply a template group to a task: creates TaskItem rows from each template.
     * Items from templates are marked is_custom = false.
     * The first item's deadline_at is set immediately from default_deadline_hours.
     */
    public function applyTemplateGroup(Task $task, string $templateGroupId): void
    {
        $group = TaskTemplateGroup::withoutGlobalScope(SchoolScope::class)
            ->findOrFail($templateGroupId);

        $templates = TaskTemplate::withoutGlobalScope(SchoolScope::class)
            ->where('group_id', $group->id)
            ->orderBy('sort_order')
            ->get();

        foreach ($templates as $index => $template) {
            TaskItem::forceCreate([
                'school_id' => $task->school_id,
                'task_id' => $task->id,
                'template_id' => $template->id,
                'group_id' => $group->id,
                'title' => $template->name,
                'is_completed' => false,
                'is_custom' => false,
                'sort_order' => $index,
                'default_deadline_hours' => $template->default_deadline_hours,
            ]);
        }

        // Set the first item's deadline immediately
        $firstItem = TaskItem::withoutGlobalScope(SchoolScope::class)
            ->where('task_id', $task->id)
            ->orderBy('sort_order')
            ->first();

        if ($firstItem !== null && $firstItem->default_deadline_hours !== null) {
            $firstItem->forceFill(['deadline_at' => now()->addHours($firstItem->default_deadline_hours)])->save();
        }
    }

    /**
     * Toggle a task item's completion.
     * Cascade: when completed, set deadline_at on the next incomplete item.
     * Unchecking does NOT reverse any previously set deadlines.
     */
    public function toggleItem(TaskItem $item): TaskItem
    {
        $nowCompleting = ! $item->is_completed;

        $item->forceFill([
            'is_completed' => $nowCompleting,
            'completed_at' => $nowCompleting ? now() : null,
        ])->save();

        if ($nowCompleting) {
            $this->cascadeDeadline($item);
        }

        return $item;
    }

    /**
     * Reorder items by accepting an ordered list of IDs.
     *
     * @param  array<int, string>  $orderedIds
     */
    public function reorder(Task $task, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            DB::table('task_items')
                ->where('task_id', $task->id)
                ->where('id', $id)
                ->update(['sort_order' => $index]);
        }
    }

    /**
     * Update the task status directly.
     */
    public function updateStatus(Task $task, string $status): Task
    {
        $task->forceFill(['status' => $status])->save();

        return $task;
    }

    /**
     * Port of CRM cascadeDeadline():
     * When an item is completed, find the next incomplete item with default_deadline_hours
     * and set its deadline_at to now() + default_deadline_hours. Never reverses deadlines.
     */
    private function cascadeDeadline(TaskItem $completedItem): void
    {
        $nextItem = TaskItem::withoutGlobalScope(SchoolScope::class)
            ->where('task_id', $completedItem->task_id)
            ->where('sort_order', '>', $completedItem->sort_order)
            ->whereNull('deadline_at')
            ->orderBy('sort_order')
            ->first();

        if ($nextItem !== null && $nextItem->default_deadline_hours !== null) {
            $nextItem->forceFill(['deadline_at' => now()->addHours($nextItem->default_deadline_hours)])->save();
        }
    }
}
