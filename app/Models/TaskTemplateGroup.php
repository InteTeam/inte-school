<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSchoolScope;
use Database\Factories\TaskTemplateGroupFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskTemplateGroup extends Model
{
    /** @use HasFactory<TaskTemplateGroupFactory> */
    use HasFactory, HasSchoolScope, HasUlids;

    protected $fillable = [
        'name',
        'department_label',
        'task_type',
    ];

    /** @return BelongsTo<School, $this> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return HasMany<TaskTemplate, $this> */
    public function templates(): HasMany
    {
        return $this->hasMany(TaskTemplate::class, 'group_id')->orderBy('sort_order');
    }
}
