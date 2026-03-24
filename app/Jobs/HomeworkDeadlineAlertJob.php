<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\School;
use App\Models\Scopes\SchoolScope;
use App\Models\Task;
use App\Services\MessagingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled low-queue job: find overdue homework with no submission → notify guardians.
 * Designed to run periodically (e.g. hourly via scheduler).
 */
class HomeworkDeadlineAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $schoolId,
    ) {
        $this->onQueue('low');
    }

    public function handle(MessagingService $messagingService): void
    {
        $school = School::find($this->schoolId);

        if ($school === null) {
            return;
        }

        // Find homework tasks that are overdue and not completed/cancelled
        $overdueTasks = Task::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $this->schoolId)
            ->where('type', 'homework')
            ->whereIn('status', ['todo', 'in_progress'])
            ->where('due_at', '<', now())
            ->whereNotNull('class_id')
            ->get();

        foreach ($overdueTasks as $task) {
            $this->notifyGuardiansForTask($task, $school, $messagingService);
        }
    }

    private function notifyGuardiansForTask(Task $task, School $school, MessagingService $messagingService): void
    {
        if ($task->class_id === null) {
            return;
        }

        // Get all students in the class
        $studentIds = DB::table('class_students')
            ->where('class_id', $task->class_id)
            ->where('school_id', $this->schoolId)
            ->whereNull('left_at')
            ->pluck('student_id')
            ->all();

        if (empty($studentIds)) {
            return;
        }

        // Find all guardians for these students
        $guardianIds = DB::table('guardian_student')
            ->where('school_id', $this->schoolId)
            ->whereIn('student_id', $studentIds)
            ->pluck('guardian_id')
            ->unique()
            ->all();

        if (empty($guardianIds)) {
            return;
        }

        // Get the teacher who assigned the homework
        $sender = \App\Models\User::find($task->assigned_by_id);

        if ($sender === null) {
            return;
        }

        try {
            $messagingService->send(
                $school,
                $sender,
                [
                    'type' => 'attendance_alert',
                    'body' => __('tasks.homework_overdue_alert', ['title' => $task->title]),
                ],
                $guardianIds,
            );
        } catch (\Throwable $e) {
            Log::warning('HomeworkDeadlineAlertJob: failed to notify guardians', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('HomeworkDeadlineAlertJob permanently failed', [
            'school_id' => $this->schoolId,
            'error' => $exception->getMessage(),
        ]);
    }
}
