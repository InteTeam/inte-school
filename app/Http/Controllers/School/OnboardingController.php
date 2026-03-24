<?php

declare(strict_types=1);

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Services\OnboardingService;
use App\Services\SchoolService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class OnboardingController extends Controller
{
    public function __construct(
        private readonly OnboardingService $onboardingService,
        private readonly SchoolService $schoolService,
    ) {}

    public function step1(): Response
    {
        return Inertia::render('School/Onboarding/Step1');
    }

    public function storeStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/'],
        ]);

        if (! $this->onboardingService->isSlugAvailable($validated['slug'])) {
            return back()->withErrors(['slug' => __('onboarding.slug_taken')]);
        }

        session(['onboarding.step1' => $validated]);

        return redirect()->route('onboarding.step2');
    }

    public function step2(): Response
    {
        if (! session('onboarding.step1')) {
            return Inertia::render('School/Onboarding/Step1');
        }

        return Inertia::render('School/Onboarding/Step2');
    }

    public function storeStep2(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'theme_primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        session(['onboarding.step2' => $request->only('theme_primary_color')]);

        return redirect()->route('onboarding.step3');
    }

    public function step3(): Response
    {
        return Inertia::render('School/Onboarding/Step3');
    }

    public function storeStep3(Request $request): RedirectResponse
    {
        session(['onboarding.step3' => ['reviewed_legal' => true]]);

        return redirect()->route('onboarding.step4');
    }

    public function step4(): Response
    {
        return Inertia::render('School/Onboarding/Step4');
    }

    public function complete(Request $request): RedirectResponse
    {
        $step1 = session('onboarding.step1');

        if (! $step1) {
            return redirect()->route('onboarding.step1');
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $logo = $request->hasFile('logo') ? $request->file('logo') : null;

        $school = $this->onboardingService->createSchoolWithAdmin(
            array_merge($step1, session('onboarding.step2', [])),
            $user,
            $logo
        );

        // Set theme if provided
        $themeColor = session('onboarding.step2.theme_primary_color');
        if ($themeColor) {
            $this->schoolService->updateTheme($school, ['primary_color' => $themeColor]);
        }

        // Clear onboarding session data
        $request->session()->forget(['onboarding.step1', 'onboarding.step2', 'onboarding.step3']);
        session(['current_school_id' => $school->id]);

        return redirect()->route('dashboard')->with([
            'alert' => __('onboarding.school_created'),
            'type' => 'success',
        ]);
    }
}
