<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    /**
     * Root admins bypass all policy checks automatically via before().
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->isRootAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Admin, teacher, and support can send messages.
     * Teachers are additionally scoped to their own classes in the controller.
     */
    public function create(User $user): bool
    {
        return $user->hasRoleInCurrentSchool(['admin', 'teacher', 'support']);
    }

    /**
     * Sender can view their own message; recipients can view messages addressed to them.
     * Admins can view all messages in their school.
     */
    public function view(User $user, Message $message): bool
    {
        if ($user->hasRoleInCurrentSchool('admin')) {
            return true;
        }

        if ($message->sender_id === $user->id) {
            return true;
        }

        return $message->recipients()
            ->where('recipient_id', $user->id)
            ->exists();
    }

    /**
     * Only admins can delete messages (soft delete).
     */
    public function delete(User $user, Message $message): bool
    {
        return $user->hasRoleInCurrentSchool('admin');
    }
}
