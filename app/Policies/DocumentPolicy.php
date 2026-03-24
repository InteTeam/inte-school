<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isRootAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Admin and teachers may upload documents.
     */
    public function create(User $user): bool
    {
        $schoolId = (string) session('current_school_id');
        $role = $user->getRoleInSchool($schoolId);

        return in_array($role, ['admin', 'teacher'], true);
    }

    /**
     * All school users may view documents.
     */
    public function view(User $user, Document $document): bool
    {
        $schoolId = (string) session('current_school_id');
        $role = $user->getRoleInSchool($schoolId);

        if (in_array($role, ['admin', 'teacher', 'support'], true)) {
            return true;
        }

        if ($role === 'parent') {
            return $document->is_parent_facing;
        }

        return false;
    }

    /**
     * Only admins may delete documents.
     */
    public function delete(User $user): bool
    {
        $schoolId = (string) session('current_school_id');

        return $user->getRoleInSchool($schoolId) === 'admin';
    }
}
