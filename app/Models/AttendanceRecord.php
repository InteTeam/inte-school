<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSchoolScope;
use Database\Factories\AttendanceRecordFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    /** @use HasFactory<AttendanceRecordFactory> */
    use HasFactory, HasSchoolScope, HasUlids;

    protected $fillable = [
        'register_id',
        'student_id',
        'status',
        'marked_by',
        'marked_via',
        'pre_notified',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'pre_notified' => 'boolean',
        ];
    }

    /** @return BelongsTo<AttendanceRegister, $this> */
    public function register(): BelongsTo
    {
        return $this->belongsTo(AttendanceRegister::class, 'register_id');
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** @return BelongsTo<User, $this> */
    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
