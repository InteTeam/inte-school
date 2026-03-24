<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

final class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService,
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('2fa:user_id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactor');
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->session()->has('2fa:user_id')) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['required_without:recovery_code', 'nullable', 'string'],
            'recovery_code' => ['required_without:code', 'nullable', 'string'],
            'remember_device' => ['boolean'],
        ]);

        $userId = $request->session()->get('2fa:user_id');
        $remember = $request->session()->get('2fa:remember', false);
        $throttleKey = 'two-factor:'.$userId;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'code' => __('auth.throttle', ['seconds' => $seconds]),
            ]);
        }

        /** @var User|null $user */
        $user = User::find($userId);

        if ($user === null) {
            $request->session()->forget(['2fa:user_id', '2fa:remember']);

            return redirect()->route('login');
        }

        $verified = false;

        if ($request->filled('code')) {
            $verified = $this->twoFactorService->verify(
                (string) $user->two_factor_secret,
                $request->input('code'),
            );
        } elseif ($request->filled('recovery_code')) {
            $verified = $this->twoFactorService->verifyRecoveryCode(
                $user,
                $request->input('recovery_code'),
            );
        }

        if (! $verified) {
            RateLimiter::hit($throttleKey, 900); // 15-minute window

            return back()->withErrors(['code' => __('auth.2fa_invalid')]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->forget(['2fa:user_id', '2fa:remember']);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $response = redirect()->intended(route('dashboard'))->with([
            'alert' => __('auth.logged_in'),
            'type' => 'success',
        ]);

        if ($request->boolean('remember_device')) {
            $token = $this->twoFactorService->createTrustedDevice(
                $user,
                $request->userAgent() ?? '',
                $request->ip() ?? '',
            );

            cookie()->queue(cookie(
                '2fa_trusted',
                $token,
                60 * 24 * 30,
                '/',
                null,
                true,
                true,
                false,
                'Strict',
            ));
        }

        return $response;
    }
}
