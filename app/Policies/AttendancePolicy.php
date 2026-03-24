<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AttendanceRegister;
use App\Models\User;

class AttendancePolicy
{
    /**
     * Root admin bypasses all checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isRootAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Teacher may only mark their own register; admin and support may mark any.
     */
    public function mark(User $user, AttendanceRegister $register): bool
    {
        $schoolId = (string) session('current_school_id');
        $role = $user->getRoleInSchool($schoolId);

        if (in_array($role, ['admin', 'support'], true)) {
            return true;
        }

        if ($role === 'teacher') {
            return $register->teacher_id === $user->id;
        }

        return false;
    }

    /**
     * Admin, support, and teachers may view attendance.
     */
    public function view(User $user): bool
    {
        $schoolId = (string) session('current_school_id');
        $role = $user->getRoleInSchool($schoolId);

        return in_array($role, ['admin', 'support', 'teacher'], true);
    }
}
