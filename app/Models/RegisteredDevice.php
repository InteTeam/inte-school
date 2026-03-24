<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSchoolScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegisteredDevice extends Model
{
    use HasSchoolScope, HasUlids;

    protected $fillable = [
        'school_id',
        'user_id',
        'device_name',
        'device_fingerprint',
        'push_subscription',
        'last_seen_at',
        'trusted_at',
    ];

    protected function casts(): array
    {
        return [
            'push_subscription' => 'array',
            'last_seen_at' => 'datetime',
            'trusted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
