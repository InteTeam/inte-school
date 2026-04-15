<?php

declare(strict_types=1);

namespace Tests\Feature\Tasks;

use App\Jobs\HomeworkDeadlineAlertJob;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Task;
use App\Models\TaskItem;
use App\Models\TaskTemplate;
use App\Models\TaskTemplateGroup;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

final class TaskTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $teacher;

    private User $parent;

    private User $student;

    private SchoolClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create([
            'notification_settings' => ['sms_fallback_enabled' => false],
        ]);

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->school->users()->attach($this->admin->id, [
            'id' => Str::ulid(), 'role' => 'admin',
            'accepted_at' => now(), 'invited_at' => now(),
        ]);

        $this->teacher = User::factory()->create(['email' => 'teacher@example.com']);
        $this->school->users()->attach($this->teacher->id, [
            'id' => Str::ulid(), 'role' => 'teacher',
            'accepted_at' => now(), 'invited_at' => now(),
        ]);

        $this->parent = User::factory()->create(['email' => 'parent@example.com']);
        $this->school->users()->attach($this->parent->id, [
            'id' => Str::ulid(), 'role' => 'parent',
            'accepted_at' => now(), 'invited_at' => now(),
        ]);

        $this->student = User::factory()->create(['email' => 'student@example.com']);
        $this->school->users()->attach($this->student->id, [
            'id' => Str::ulid(), 'role' => 'student',
            'accepted_at' => now(), 'invited_at' => now(),
        ]);

        $this->class = SchoolClass::forceCreate([
            'school_id' => $this->school->id,
            'name' => 'Class 1A',
            'year_group' => 'Year 1',
            'teacher_id' => $this->teacher->id,
        ]);

        \DB::table('class_students')->insert([
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'enrolled_at' => now(),
        ]);

        \DB::table('guardian_student')->insert([
            'id' => (string) Str::ulid(),
            'school_id' => $this->school->id,
            'guardian_id' => $this->parent->id,
            'student_id' => $this->student->id,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // --- Staff task CRUD ---

    public function test_teacher_can_create_staff_task(): void
    {
        $service = app(TaskService::class);
        $task = $service->createTask($this->school->id, $this->teacher, [
            'title' => 'Update lesson plan',
            'priority' => 'medium',
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'type' => 'staff_task',
            'title' => 'Update lesson plan',
            'assigned_by_id' => $this->teacher->id,
        ]);
    }

    // --- Homework assign to class ---

    public function test_teacher_can_assign_homework_to_class(): void
    {
        $service = app(TaskService::class);
        $task = $service->createHomework($this->school->id, $this->teacher, $this->class->id, [
            'title' => 'Read Chapter 5',
            'due_at' => now()->addWeek(),
        ]);

        $this->assertSame('homework', $task->type);
        $this->assertSame($this->class->id, $task->class_id);
        $this->assertDatabaseHas('tasks', ['type' => 'homework', 'class_id' => $this->class->id]);
    }

    // --- Action item from message ---

    public function test_action_item_created_from_message(): void
    {
        // Create a message first
        $message = \App\Models\Message::forceCreate([
            'school_id' => $this->school->id,
            'sender_id' => $this->admin->id,
            'transaction_id' => (string) Str::ulid(),
            'type' => 'announcement',
            'body' => 'Please follow up on this',
            'requires_read_receipt' => false,
            'sent_at' => now(),
        ]);

        $service = app(TaskService::class);
        $task = $service->createActionItem($this->school->id, $this->admin, $message->id, [
            'title' => 'Follow up on announcement',
        ]);

        $this->assertSame('action_item', $task->type);
        $this->assertSame($message->id, $task->source_message_id);
    }

    // --- Grouped todo template apply (cascade deadlines) ---

    public function test_apply_template_group_creates_items_with_is_custom_false(): void
    {
        $service = app(TaskService::class);
        $task = $service->createTask($this->school->id, $this->admin, ['title' => 'Term Start']);

        $group = TaskTemplateGroup::forceCreate([
            'school_id' => $this->school->id,
            'name' => 'Term Start Checklist',
            'task_type' => 'staff',
        ]);

        TaskTemplate::forceCreate([
            'school_id' => $this->school->id,
            'group_id' => $group->id,
            'name' => 'Send welcome letter',
            'sort_order' => 0,
            'default_deadline_hours' => 24,
        ]);
        TaskTemplate::forceCreate([
            'school_id' => $this->school->id,
            'group_id' => $group->id,
            'name' => 'Set up classroom',
            'sort_order' => 1,
            'default_deadline_hours' => 48,
        ]);

        $service->applyTemplateGroup($task, $group->id);

        $items = TaskItem::withoutGlobalScope(\App\Models\Scopes\SchoolScope::class)
            ->where('task_id', $task->id)
            ->orderBy('sort_order')
            ->get();

        $this->assertCount(2, $items);
        $this->assertFalse((bool) $items[0]->is_custom);
        $this->assertFalse((bool) $items[1]->is_custom);

        // First item should have deadline_at set
        $this->assertNotNull($items[0]->deadline_at);
        // Second item should NOT have deadline_at yet (cascade)
        $this->assertNull($items[1]->deadline_at);
    }

    // --- Cascade deadline chain ---

    public function test_cascade_deadline_sets_next_item_deadline_when_item_completed(): void
    {
        $service = app(TaskService::class);
        $task = $service->createTask($this->school->id, $this->admin, ['title' => 'Chain Task']);

        $item1 = TaskItem::forceCreate([
            'school_id' => $this->school->id,
            'task_id' => $task->id,
            'title' => 'Step 1',
            'sort_order' => 0,
            'default_deadline_hours' => 24,
            'deadline_at' => now()->addDay(),
        ]);
        $item2 = TaskItem::forceCreate([
            'school_id' => $this->school->id,
            'task_id' => $task->id,
            'title' => 'Step 2',
            'sort_order' => 1,
            'default_deadline_hours' => 48,
        ]);

        $this->assertNull($item2->deadline_at);

        $service->toggleItem($item1);

        $item2->refresh();
        $this->assertNotNull($item2->deadline_at);
    }

    // --- Unchecking does NOT reverse deadline ---

    public function test_unchecking_item_does_not_reverse_next_item_deadline(): void
    {
        $service = app(TaskService::class);
        $task = $service->createTask($this->school->id, $this->admin, ['title' => 'No reverse task']);

        $item1 = TaskItem::forceCreate([
            'school_id' => $this->school->id,
            'task_id' => $task->id,
            'title' => 'Step 1',
            'sort_order' => 0,
            'is_completed' => true,
            'default_deadline_hours' => 24,
        ]);
        $item2 = TaskItem::forceCreate([
            'school_id' => $this->school->id,
            'task_id' => $task->id,
            'title' => 'Step 2',
            'sort_order' => 1,
            'default_deadline_hours' => 48,
            'deadline_at' => now()->addDays(2), // already set from previous toggle
        ]);

        $service->toggleItem($item1); // unchecking item1

        $item2->refresh();
        // deadline_at should remain unchanged
        $this->assertNotNull($item2->deadline_at);
    }

    // --- Drag reorder persists ---

    public function test_reorder_persists_sort_order(): void
    {
        $service = app(TaskService::class);
        $task = $service->createTask($this->school->id, $this->admin, ['title' => 'Reorder task']);

        $item1 = TaskItem::forceCreate([
            'school_id' => $this->school->id,
            'task_id' => $task->id,
            'title' => 'A',
            'sort_order' => 0,
        ]);
        $item2 = TaskItem::forceCreate([
            'school_id' => $this->school->id,
            'task_id' => $task->id,
            'title' => 'B',
            'sort_order' => 1,
        ]);

        // Drag B before A
        $service->reorder($task, [$item2->id, $item1->id]);

        $item1->refresh();
        $item2->refresh();

        $this->assertSame(1, $item1->sort_order);
        $this->assertSame(0, $item2->sort_order);
    }

    // --- Parent notified when homework deadline passes ---

    public function test_parent_notified_when_homework_deadline_passes(): void
    {
        Queue::fake();

        $task = Task::forceCreate([
            'school_id' => $this->school->id,
            'type' => 'homework',
            'title' => 'Overdue homework',
            'status' => 'todo',
            'assigned_by_id' => $this->teacher->id,
            'class_id' => $this->class->id,
            'due_at' => now()->subHour(), // overdue
        ]);

        $job = new HomeworkDeadlineAlertJob($this->school->id);
        $job->handle(app(\App\Services\MessagingService::class));

        // Should have dispatched SendBulkMessageJob or fired directly for the guardian
        $this->assertDatabaseHas('messages', ['type' => 'attendance_alert']);
    }

    // --- SOP: Guest redirect ---

    public function test_guest_cannot_access_tasks(): void
    {
        $this->get(route('teacher.tasks.index'))->assertRedirect('/login');
    }

    public function test_guest_cannot_create_task(): void
    {
        $this->post(route('teacher.tasks.store'), [])->assertRedirect('/login');
    }

    // --- SOP: Wrong role ---

    public function test_parent_cannot_access_task_routes(): void
    {
        $parent = User::factory()->create(['email' => 'task-parent@example.com']);
        $this->school->users()->attach($parent->id, [
            'id' => Str::ulid(), 'role' => 'parent',
            'accepted_at' => now(), 'invited_at' => now(),
        ]);

        $this->withoutExceptionHandling();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->actingAs($parent)
            ->withSession(['current_school_id' => $this->school->id])
            ->get(route('teacher.tasks.index'));
    }

    // --- SOP: Multi-tenant isolation ---

    public function test_tasks_scoped_to_school(): void
    {
        Task::forceCreate([
            'school_id' => $this->school->id,
            'type' => 'staff_task',
            'title' => 'School A task',
            'status' => 'todo',
            'assigned_by_id' => $this->teacher->id,
        ]);

        $otherSchool = School::factory()->create();
        $this->actingAs($this->admin)->withSession(['current_school_id' => $otherSchool->id]);

        $visible = Task::where('title', 'School A task')->count();
        $this->assertSame(0, $visible);
    }
}
