<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\School;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Scopes\SchoolScope;
use Illuminate\Support\Facades\Log;

class ProcessStudentCsvImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @param array<int, array<string, string>> $rows */
    public function __construct(
        private readonly School $school,
        private readonly User $enrolledBy,
        private readonly array $rows,
    ) {
        $this->onQueue('default');
    }

    public function handle(UserManagementService $service): void
    {
        foreach (array_chunk($this->rows, 50) as $chunk) {
            foreach ($chunk as $row) {
                try {
                    if (empty($row['name']) || empty($row['email'])) {
                        continue;
                    }

                    $data = [
                        'name' => $row['name'],
                        'email' => $row['email'],
                    ];

                    if (! empty($row['class_name'])) {
                        $class = \App\Models\SchoolClass::query()
                            ->withoutGlobalScope(SchoolScope::class)
                            ->where('school_id', $this->school->id)
                            ->where('name', $row['class_name'])
                            ->first();

                        if ($class !== null) {
                            $data['class_id'] = $class->id;
                        }
                    }

                    $service->enrolStudent($this->school, $data, $this->enrolledBy);
                } catch (\Throwable $e) {
                    Log::warning('CSV import row failed', [
                        'school_id' => $this->school->id,
                        'row' => $row,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
