<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSchoolScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HardwareDeviceToken extends Model
{
    use HasSchoolScope, HasUlids;

    protected $fillable = [
        'name',
        'token_hash',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<School, $this> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Find a device token by raw token value within a school.
     */
    public static function findByToken(string $schoolId, string $rawToken): ?self
    {
        return self::withoutGlobalScope(\App\Models\Scopes\SchoolScope::class)
            ->where('school_id', $schoolId)
            ->where('token_hash', hash('sha256', $rawToken))
            ->first();
    }
}
