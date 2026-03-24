<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class SchoolScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        // Skip scope if no authenticated user (queues, console, tests without auth)
        if ($user === null) {
            return;
        }

        $schoolId = session('current_school_id');

        if ($schoolId === null) {
            // User not in a school context — return empty result set
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->getTable().'.school_id', $schoolId);
    }
}
