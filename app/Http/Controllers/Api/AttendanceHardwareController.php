<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HardwareDeviceToken;
use App\Models\School;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceHardwareController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_token' => ['required', 'string'],
            'card_id' => ['required', 'string'],
            'school_id' => ['required', 'string'],
            'timestamp' => ['required', 'string'],
        ]);

        // Authenticate device token
        $deviceToken = HardwareDeviceToken::findByToken(
            $validated['school_id'],
            $validated['device_token'],
        );

        if ($deviceToken === null) {
            return response()->json(['error' => 'Invalid device token'], 401);
        }

        // Resolve student from NFC card
        $student = User::where('nfc_card_id', $validated['card_id'])->first();

        if ($student === null) {
            return response()->json(['error' => 'Card not registered to any student'], 404);
        }

        $school = School::find($validated['school_id']);

        if ($school === null) {
            return response()->json(['error' => 'School not found'], 404);
        }

        // NFC swipe → present by default (hardware can be configured otherwise)
        $register = $this->attendanceService->openOrGetRegister(
            $school,
            $student, // student as teacher proxy for hardware-opened register
            $student->classes()->first()->id ?? '',
            now(),
        );

        if (empty($register->class_id)) {
            return response()->json(['error' => 'Student not enrolled in any class'], 422);
        }

        $record = $this->attendanceService->mark(
            $register,
            $student->id,
            'present',
            $student, // marked_by — use student's own user for hardware marks
            'nfc_card',
        );

        // Update last_used_at on device token
        $deviceToken->update(['last_used_at' => now()]);

        return response()->json([
            'status' => 'marked',
            'student_name' => $student->name,
            'attendance_status' => $record->status,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
