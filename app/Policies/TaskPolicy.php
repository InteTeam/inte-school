<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isRootAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Staff tasks: admin, teacher, support.
     * Homework: teacher (own class enforced in service/controller).
     */
    public function create(User $user): bool
    {
        $schoolId = (string) session('current_school_id');
        $role = $user->getRoleInSchool($schoolId);

        return in_array($role, ['admin', 'teacher', 'support'], true);
    }

    /**
     * Admins may update any task; teachers and support may update tasks assigned to them.
     */
    public function update(User $user, Task $task): bool
    {
        $schoolId = (string) session('current_school_id');
        $role = $user->getRoleInSchool($schoolId);

        if ($role === 'admin') {
            return true;
        }

        if (in_array($role, ['teacher', 'support'], true)) {
            return $task->assignee_id === $user->id || $task->assigned_by_id === $user->id;
        }

        return false;
    }

    /**
     * Only admins may delete tasks.
     */
    public function delete(User $user, Task $task): bool
    {
        $schoolId = (string) session('current_school_id');

        return $user->getRoleInSchool($schoolId) === 'admin';
    }
}
