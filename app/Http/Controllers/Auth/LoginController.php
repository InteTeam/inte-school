<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class LoginController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'status' => session('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember', false);

        if (! Auth::validate($credentials)) {
            return back()->withErrors([
                'email' => __('auth.failed'),
            ])->onlyInput('email');
        }

        /** @var User $user */
        $user = User::where('email', $request->input('email'))->first();

        if ($user->hasTwoFactorEnabled()) {
            $trustedToken = $request->cookie('2fa_trusted');
            if ($trustedToken && $this->twoFactorService->isDeviceTrusted($user, $trustedToken)) {
                return $this->completeLogin($request, $user, $remember);
            }

            $request->session()->put('2fa:user_id', $user->id);
            $request->session()->put('2fa:remember', $remember);

            return redirect()->route('two-factor.challenge');
        }

        return $this->completeLogin($request, $user, $remember);
    }

    public function destroy(): RedirectResponse
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login')->with([
            'alert' => __('auth.logged_out'),
            'type' => 'success',
        ]);
    }

    private function completeLogin(LoginRequest $request, User $user, bool $remember): RedirectResponse
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();

        // School context is set by EnsureSchoolContext middleware on each request
        // Direct assignment happens in P1.4 when school_user pivot is available

        return redirect()->intended(route('dashboard'))->with([
            'alert' => __('auth.logged_in'),
            'type' => 'success',
        ]);
    }
}
