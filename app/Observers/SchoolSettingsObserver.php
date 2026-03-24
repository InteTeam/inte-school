<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\School;
use Illuminate\Support\Facades\Cache;

class SchoolSettingsObserver
{
    public function saved(School $school): void
    {
        $this->flush($school->id);
    }

    public function deleted(School $school): void
    {
        $this->flush($school->id);
    }

    private function flush(string $schoolId): void
    {
        Cache::forget("school:{$schoolId}:settings");
        Cache::forget("school:{$schoolId}:features");
        Cache::forget("school:{$schoolId}:notification_settings");
    }
}
