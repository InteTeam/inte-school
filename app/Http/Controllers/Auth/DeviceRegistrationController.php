<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\RegisteredDevice;
use App\Models\Scopes\SchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DeviceRegistrationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/DeviceRegistration');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'device_name' => ['required', 'string', 'max:100'],
            'device_fingerprint' => ['required', 'string'],
            'push_subscription' => ['nullable', 'array'],
            'push_subscription.endpoint' => ['required_with:push_subscription', 'string', 'url'],
            'push_subscription.keys.p256dh' => ['required_with:push_subscription', 'string'],
            'push_subscription.keys.auth' => ['required_with:push_subscription', 'string'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        // Update or create device record (upsert by fingerprint + user)
        RegisteredDevice::withoutGlobalScope(SchoolScope::class)->updateOrCreate(
            [
                'user_id' => $user->id,
                'device_fingerprint' => $request->input('device_fingerprint'),
            ],
            [
                'school_id' => session('current_school_id'),
                'device_name' => $request->input('device_name'),
                'push_subscription' => $request->input('push_subscription'),
                'last_seen_at' => now(),
            ],
        );

        return redirect()->intended(route('dashboard'));
    }
}
