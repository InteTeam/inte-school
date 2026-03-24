<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ClassStudent extends Pivot
{
    protected $table = 'class_students';

    public $incrementing = false;

    protected $fillable = [
        'class_id',
        'student_id',
        'school_id',
        'enrolled_at',
        'left_at',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }
}
