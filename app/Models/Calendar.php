<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSchoolScope;
use App\Policies\CalendarPolicy;
use Database\Factories\CalendarFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UsePolicy(CalendarPolicy::class)]
class Calendar extends Model
{
    /** @use HasFactory<CalendarFactory> */
    use HasFactory, HasSchoolScope, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'department_label',
        'color',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    /** @return BelongsTo<School, $this> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return HasMany<CalendarEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }
}
