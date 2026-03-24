<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSchoolScope;
use Database\Factories\TaskTemplateFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskTemplate extends Model
{
    /** @use HasFactory<TaskTemplateFactory> */
    use HasFactory, HasSchoolScope, HasUlids;

    protected $fillable = [
        'group_id',
        'name',
        'sort_order',
        'default_deadline_hours',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'default_deadline_hours' => 'integer',
        ];
    }

    /** @return BelongsTo<TaskTemplateGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(TaskTemplateGroup::class, 'group_id');
    }
}
