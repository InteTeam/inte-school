<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\School;
use Illuminate\Http\UploadedFile;

final class SchoolService
{
    public function __construct(
        private readonly StorageService $storageService,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(array $data): School
    {
        return School::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'custom_domain' => $data['custom_domain'] ?? null,
            'plan' => $data['plan'] ?? 'standard',
            'theme_config' => $data['theme_config'] ?? [],
            'settings' => $data['settings'] ?? [],
            'notification_settings' => array_merge(
                ['sms_timeout_seconds' => 900, 'sms_fallback_enabled' => false],
                $data['notification_settings'] ?? []
            ),
            'security_policy' => $data['security_policy'] ?? [],
        ]);
    }

    /** @param array<string, mixed> $settings */
    public function updateSettings(School $school, array $settings): School
    {
        $school->settings = array_merge($school->settings ?? [], $settings);
        $school->save();

        return $school;
    }

    /** @param array<string, mixed> $settings */
    public function updateNotificationSettings(School $school, array $settings): School
    {
        $school->notification_settings = array_merge($school->notification_settings ?? [], $settings);
        $school->save();

        return $school;
    }

    /** @param array<string, mixed> $themeConfig */
    public function updateTheme(School $school, array $themeConfig): School
    {
        $school->theme_config = array_merge($school->theme_config ?? [], $themeConfig);
        $school->save();

        return $school;
    }

    public function uploadLogo(School $school, UploadedFile $file): School
    {
        if ($school->logo_path) {
            $this->storageService->delete($school->logo_path);
        }

        $path = $file->store("schools/{$school->id}/logos", config('filesystems.default', 'local'));

        $school->logo_path = $path;
        $school->save();

        return $school;
    }
}
