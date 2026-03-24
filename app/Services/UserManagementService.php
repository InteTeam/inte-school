<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GuardianStudent;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Scopes\SchoolScope;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class UserManagementService
{
    private const INJECTION_PREFIXES = ['=', '-', '+', '@', "\t", "\r"];

    /**
     * Invite a staff member by generating an invitation token and attaching them to the school.
     *
     * @param array<string, mixed> $data
     */
    public function inviteStaff(School $school, array $data, User $invitedBy): User
    {
        $user = User::where('email', $data['email'])->first();

        if ($user === null) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make(Str::random(32)),
            ]);
        }

        $token = Str::random(40);

        $school->users()->attach($user->id, [
            'id' => (string) Str::ulid(),
            'role' => $data['role'],
            'department_label' => $data['department_label'] ?? null,
            'invitation_token' => $token,
            'invitation_expires_at' => now()->addDays(7),
            'invited_by' => $invitedBy->id,
            'invited_at' => now(),
        ]);

        return $user->fresh();
    }

    /**
     * Accept a staff invitation by token, setting name and password.
     *
     * @param array<string, mixed> $data
     */
    public function acceptInvitation(string $token, array $data): bool
    {
        $result = \DB::table('school_user')
            ->where('invitation_token', $token)
            ->where('invitation_expires_at', '>', now())
            ->whereNull('accepted_at')
            ->first();

        if ($result === null) {
            return false;
        }

        $user = User::find($result->user_id);

        if ($user === null) {
            return false;
        }

        $user->update([
            'name' => $data['name'],
            'password' => Hash::make($data['password']),
        ]);

        \DB::table('school_user')
            ->where('invitation_token', $token)
            ->update([
                'accepted_at' => now(),
                'invitation_token' => null,
            ]);

        return true;
    }

    /**
     * Enrol a student into the school, optionally assigning to a class.
     *
     * @param array<string, mixed> $data
     */
    public function enrolStudent(School $school, array $data, User $enrolledBy): User
    {
        $student = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make(Str::random(32)),
        ]);

        $school->users()->attach($student->id, [
            'id' => (string) Str::ulid(),
            'role' => 'student',
            'accepted_at' => now(),
            'invited_by' => $enrolledBy->id,
            'invited_at' => now(),
        ]);

        if (isset($data['class_id'])) {
            $this->addStudentToClass($school, $data['class_id'], $student->id);
        }

        return $student->fresh();
    }

    public function addStudentToClass(School $school, string $classId, string $studentId): void
    {
        \DB::table('class_students')->insertOrIgnore([
            'class_id' => $classId,
            'student_id' => $studentId,
            'school_id' => $school->id,
            'enrolled_at' => now(),
        ]);
    }

    public function removeStudentFromClass(string $classId, string $studentId): void
    {
        \DB::table('class_students')
            ->where('class_id', $classId)
            ->where('student_id', $studentId)
            ->update(['left_at' => now()]);
    }

    /**
     * Generate a guardian invite code (stored as invitation_token on a pending school_user row).
     */
    public function generateGuardianInviteCode(School $school, string $studentId, User $generatedBy): string
    {
        $code = strtoupper(Str::random(8));

        \DB::table('school_user')->insert([
            'id' => (string) Str::ulid(),
            'school_id' => $school->id,
            'user_id' => null,
            'role' => 'parent',
            'invitation_token' => $code,
            'invitation_expires_at' => now()->addDays(14),
            'invited_by' => $generatedBy->id,
            'invited_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
            // Store student_id for linking after registration
            'department_label' => $studentId,
        ]);

        return $code;
    }

    /**
     * Link a guardian to a student.
     */
    public function linkGuardianToStudent(School $school, string $guardianId, string $studentId, bool $isPrimary = true): GuardianStudent
    {
        // Bypass global scope and fillable restriction — school_id comes from the explicit $school param
        $existing = GuardianStudent::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $school->id)
            ->where('guardian_id', $guardianId)
            ->where('student_id', $studentId)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        /** @var GuardianStudent $link */
        $link = GuardianStudent::forceCreate([
            'school_id' => $school->id,
            'guardian_id' => $guardianId,
            'student_id' => $studentId,
            'is_primary' => $isPrimary,
        ]);

        return $link;
    }

    /**
     * Process a CSV row array, sanitizing values to prevent CSV injection.
     *
     * @param array<int|string, string> $row
     * @return array<int|string, string>
     */
    public function sanitizeCsvRow(array $row): array
    {
        return array_map(function (string $value): string {
            $trimmed = ltrim($value);
            if ($trimmed !== '' && in_array($trimmed[0], self::INJECTION_PREFIXES, true)) {
                return "'" . $trimmed;
            }

            return $value;
        }, $row);
    }

    /**
     * Generate a CSV export template for student bulk import.
     *
     * @return array<int, array<string>>
     */
    public function csvImportTemplate(): array
    {
        return [
            ['name', 'email', 'year_group', 'class_name'],
        ];
    }

    /**
     * Parse and validate a CSV file for student import.
     * Returns sanitized rows ready for ProcessStudentCsvImportJob.
     *
     * @param  resource  $handle
     * @return array<int, array<string, string>>
     */
    public function parseCsvImport(mixed $handle): array
    {
        $rows = [];
        $headers = null;

        while (($raw = fgetcsv($handle)) !== false) {
            /** @var array<int, string> $raw */
            $row = $this->sanitizeCsvRow($raw);

            if ($headers === null) {
                $headers = array_map('trim', $row);
                continue;
            }

            if (count($row) !== count($headers)) {
                continue;
            }

            /** @var array<string, string> $combined */
            $combined = array_combine($headers, $row);
            $rows[] = $combined;
        }

        return $rows;
    }
}
