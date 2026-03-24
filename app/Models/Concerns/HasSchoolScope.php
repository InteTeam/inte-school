<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Builder;

trait HasSchoolScope
{
    /**
     * Boot the trait — register global scope and auto-set school_id on creation.
     */
    protected static function bootHasSchoolScope(): void
    {
        static::addGlobalScope(new SchoolScope);

        static::creating(function ($model) {
            if ($model->school_id === null) {
                $model->school_id = session('current_school_id');
            }
        });
    }

    /**
     * Scope a query to a specific school, bypassing the global SchoolScope.
     * Use this for root-admin cross-school queries only.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForSchool(Builder $query, string $schoolId): Builder
    {
        return $query->withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $schoolId);
    }
}
