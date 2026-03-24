<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Private channels require auth — the closure returns truthy to allow,
| falsy to deny. School-scoped channel: user must be an active member.
| User channel: user can only subscribe to their own channel.
|
*/

// Private per-user channel — push notifications, presence
Broadcast::channel('user.{userId}', function (\App\Models\User $user, string $userId): bool {
    return $user->id === $userId;
});

// Private per-school channel — school-wide broadcasts (admin announcements, alerts)
Broadcast::channel('school.{schoolId}', function (\App\Models\User $user, string $schoolId): bool {
    if ($user->isRootAdmin()) {
        return true;
    }

    $sessionSchoolId = session('current_school_id');

    if ($sessionSchoolId !== $schoolId) {
        return false;
    }

    return \Illuminate\Support\Facades\DB::table('school_user')
        ->where('school_id', $schoolId)
        ->where('user_id', $user->id)
        ->whereNotNull('accepted_at')
        ->exists();
});
