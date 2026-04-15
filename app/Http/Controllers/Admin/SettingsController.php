<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateGeneralSettingsRequest;
use App\Http\Requests\Admin\UpdateNotificationSettingsRequest;
use App\Http\Requests\Admin\UpdateSecuritySettingsRequest;
use App\Models\School;
use App\Models\SchoolLegalDocument;
use App\Services\SchoolService;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SchoolService $schoolService,
    ) {}

    public function general(): Response
    {
        $school = $this->currentSchool();

        return Inertia::render('Admin/Settings/General', [
            'school' => [
                'id' => $school->id,
                'name' => $school->name,
                'slug' => $school->slug,
                'logo_url' => $school->logoUrl(),
                'theme_config' => $school->theme_config ?? [],
            ],
        ]);
    }

    public function updateGeneral(UpdateGeneralSettingsRequest $request): RedirectResponse
    {
        $school = $this->currentSchool();
        $validated = $request->validated();

        $school->name = $validated['name'];
        $school->save();

        if (isset($validated['theme_config'])) {
            $this->schoolService->updateTheme($school, $validated['theme_config']);
        }

        if ($request->hasFile('logo')) {
            $this->schoolService->uploadLogo($school, $request->file('logo'));
        }

        return redirect()->route('admin.settings.general')
            ->with(['alert' => __('settings.general_updated'), 'type' => 'success']);
    }

    public function notifications(): Response
    {
        $school = $this->currentSchool();
        $smsService = app(SmsService::class);
        $settings = $school->notification_settings ?? [];

        return Inertia::render('Admin/Settings/Notifications', [
            'notification_settings' => $settings,
            'has_notify_key' => ! empty($settings['govuk_notify_api_key']),
            'has_notify_template' => ! empty($settings['govuk_notify_template_id']),
            'sms_usage' => [
                'sent_this_year' => $smsService->getUsageThisYear($school->id),
                'free_remaining' => $smsService->getRemainingFreeTexts($school->id),
                'free_allowance' => (int) config('services.govuk_notify.free_allowance', 5000),
                'approaching_limit' => $smsService->isApproachingLimit($school->id),
            ],
        ]);
    }

    public function updateNotifications(UpdateNotificationSettingsRequest $request): RedirectResponse
    {
        $school = $this->currentSchool();
        $validated = $request->validated();

        // Encrypt the API key before storing — never store in plaintext
        if (! empty($validated['govuk_notify_api_key'])) {
            $validated['govuk_notify_api_key'] = Crypt::encryptString($validated['govuk_notify_api_key']);
        } else {
            // If empty, don't overwrite existing key (allow partial update)
            unset($validated['govuk_notify_api_key']);
        }

        $this->schoolService->updateNotificationSettings($school, $validated);

        return redirect()->route('admin.settings.notifications')
            ->with(['alert' => __('settings.notifications_updated'), 'type' => 'success']);
    }

    public function security(): Response
    {
        $school = $this->currentSchool();

        return Inertia::render('Admin/Settings/Security', [
            'security_policy' => $school->security_policy ?? [],
            'plan' => $school->plan,
        ]);
    }

    public function updateSecurity(UpdateSecuritySettingsRequest $request): RedirectResponse
    {
        $school = $this->currentSchool();

        $school->security_policy = array_merge($school->security_policy ?? [], $request->validated());
        $school->save();

        return redirect()->route('admin.settings.security')
            ->with(['alert' => __('settings.security_updated'), 'type' => 'success']);
    }

    public function legal(): Response
    {
        $school = $this->currentSchool();

        $documents = SchoolLegalDocument::query()
            ->whereIn('type', ['privacy_policy', 'terms_conditions'])
            ->orderBy('type')
            ->get()
            ->map(fn ($doc) => [
                'id' => $doc->id,
                'type' => $doc->type,
                'version' => $doc->version,
                'is_published' => $doc->is_published,
                'published_at' => $doc->published_at?->toIso8601String(),
                'edit_url' => route('legal.edit', $doc->id),
            ]);

        return Inertia::render('Admin/Settings/Legal', [
            'documents' => $documents,
        ]);
    }

    private function currentSchool(): School
    {
        $schoolId = session('current_school_id');

        /** @var School $school */
        $school = School::find($schoolId);

        return $school;
    }
}
