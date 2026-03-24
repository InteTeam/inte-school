<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AcceptInvitationController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        $token = $request->query('token', '');

        if ($token === '') {
            return redirect()->route('login');
        }

        $pending = \DB::table('school_user')
            ->where('invitation_token', $token)
            ->where('invitation_expires_at', '>', now())
            ->whereNull('accepted_at')
            ->first();

        if ($pending === null) {
            return redirect()->route('login')
                ->with(['alert' => __('auth.invitation_invalid'), 'type' => 'error']);
        }

        return Inertia::render('Auth/AcceptInvitation', [
            'token' => $token,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->uncompromised()],
        ]);

        $accepted = $this->userManagementService->acceptInvitation(
            $request->string('token')->toString(),
            $request->only('name', 'password')
        );

        if (! $accepted) {
            return redirect()->back()
                ->withErrors(['token' => __('auth.invitation_invalid')]);
        }

        return redirect()->route('login')
            ->with(['alert' => __('auth.invitation_accepted'), 'type' => 'success']);
    }
}
