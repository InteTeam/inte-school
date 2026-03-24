<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSchoolScope;
use App\Models\Scopes\SchoolScope;
use Database\Factories\SchoolApiKeyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolApiKey extends Model
{
    /** @use HasFactory<SchoolApiKeyFactory> */
    use HasFactory, HasSchoolScope, HasUlids;

    protected $fillable = [
        'name',
        'key_hash',
        'permissions',
        'created_by',
        'last_used_at',
        'expires_at',
    ];

    protected $hidden = ['key_hash'];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<School, $this> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Locate an API key by its raw token value — school-agnostic lookup.
     */
    public static function findByKey(string $rawKey): ?self
    {
        return self::withoutGlobalScope(SchoolScope::class)
            ->where('key_hash', hash('sha256', $rawKey))
            ->first();
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, (array) $this->permissions, true);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null
            && \Carbon\Carbon::parse((string) $this->expires_at)->isPast();
    }
}
