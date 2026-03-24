<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;

class CalendarPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isRootAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Admin and teachers may create calendars and events.
     */
    public function create(User $user): bool
    {
        $schoolId = (string) session('current_school_id');
        $role = $user->getRoleInSchool($schoolId);

        return in_array($role, ['admin', 'teacher'], true);
    }

    /**
     * Admin may edit any event; teachers may only edit their own.
     */
    public function update(User $user, CalendarEvent $event): bool
    {
        $schoolId = (string) session('current_school_id');
        $role = $user->getRoleInSchool($schoolId);

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'teacher') {
            return $event->created_by === $user->id;
        }

        return false;
    }

    /**
     * Admin may delete any event; teachers may delete their own.
     */
    public function delete(User $user, CalendarEvent $event): bool
    {
        return $this->update($user, $event);
    }

    /**
     * Parents and students may view external (is_public) calendars.
     */
    public function viewExternal(User $user): bool
    {
        $schoolId = (string) session('current_school_id');
        $role = $user->getRoleInSchool($schoolId);

        return in_array($role, ['parent', 'student'], true);
    }
}
