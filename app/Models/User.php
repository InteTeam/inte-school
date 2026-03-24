<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUlids, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'whatsapp_number',
        'nfc_card_id',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (! static::query()->exists()) {
                $user->is_root_admin = true;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_root_admin' => 'boolean',
            'disabled_at' => 'datetime',
        ];
    }

    public function isRootAdmin(): bool
    {
        return $this->is_root_admin === true;
    }

    public function isDisabled(): bool
    {
        return $this->disabled_at !== null;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null;
    }

    /** @return BelongsToMany<School, $this> */
    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'school_user')
            ->withPivot(['role', 'department_label', 'invitation_token', 'invitation_expires_at', 'accepted_at', 'invited_by', 'invited_at'])
            ->withTimestamps();
    }

    public function getRoleInSchool(string $schoolId): ?string
    {
        /** @var object{role: string}|null $pivot */
        $pivot = $this->schools()->where('schools.id', $schoolId)->first()?->pivot;

        return $pivot?->role;
    }

    public function currentSchoolRole(): ?string
    {
        $schoolId = session('current_school_id');

        if ($schoolId === null) {
            return null;
        }

        return $this->getRoleInSchool($schoolId);
    }

    /** @param string|array<int, string> $roles */
    public function hasRoleInCurrentSchool(string|array $roles): bool
    {
        $currentRole = $this->currentSchoolRole();

        if ($currentRole === null) {
            return false;
        }

        return in_array($currentRole, (array) $roles, true);
    }

    /** @return HasMany<RegisteredDevice, $this> */
    public function registeredDevices(): HasMany
    {
        return $this->hasMany(RegisteredDevice::class);
    }

    /** @return HasMany<ActionToken, $this> */
    public function actionTokens(): HasMany
    {
        return $this->hasMany(ActionToken::class, 'recipient_id');
    }

    /** @return BelongsToMany<SchoolClass, $this> */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_students', 'student_id', 'class_id')
            ->withPivot(['school_id', 'enrolled_at', 'left_at'])
            ->wherePivotNull('left_at');
    }

    /** @return HasMany<GuardianStudent, $this> */
    public function guardianLinks(): HasMany
    {
        return $this->hasMany(GuardianStudent::class, 'guardian_id');
    }

    /** @return HasMany<GuardianStudent, $this> */
    public function studentGuardianLinks(): HasMany
    {
        return $this->hasMany(GuardianStudent::class, 'student_id');
    }
}
