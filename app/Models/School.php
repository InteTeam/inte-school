<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\StorageService;
use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property array<string, mixed> $theme_config
 * @property array<string, mixed> $settings
 * @property array<string, mixed> $notification_settings
 * @property array<string, mixed> $security_policy
 */
class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'custom_domain',
        'logo_path',
        'theme_config',
        'settings',
        'notification_settings',
        'security_policy',
        'plan',
        'rag_enabled',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'theme_config' => 'array',
            'settings' => 'array',
            'notification_settings' => 'array',
            'security_policy' => 'array',
            'rag_enabled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function logoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return app(StorageService::class)->url($this->logo_path);
    }

    public function getNotificationSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->notification_settings, $key, $default);
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'school_user')
            ->withPivot(['role', 'department_label', 'accepted_at'])
            ->withTimestamps();
    }

    /** @return BelongsToMany<User, $this> */
    public function usersWithRole(string $role): BelongsToMany
    {
        return $this->users()->wherePivot('role', $role);
    }

    public function isFeatureEnabled(string $feature): bool
    {
        if ($feature === 'rag') {
            return $this->rag_enabled;
        }

        return (bool) data_get($this->settings, "features.{$feature}", false);
    }
}
