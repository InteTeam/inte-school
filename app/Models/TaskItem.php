<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSchoolScope;
use Database\Factories\TaskItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskItem extends Model
{
    /** @use HasFactory<TaskItemFactory> */
    use HasFactory, HasSchoolScope, HasUlids;

    protected $fillable = [
        'task_id',
        'template_id',
        'group_id',
        'title',
        'is_completed',
        'is_custom',
        'sort_order',
        'deadline_at',
        'default_deadline_hours',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'is_custom' => 'boolean',
            'deadline_at' => 'datetime',
            'completed_at' => 'datetime',
            'sort_order' => 'integer',
            'default_deadline_hours' => 'integer',
        ];
    }

    /** @return BelongsTo<Task, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
