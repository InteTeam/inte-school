<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'isRootAdmin' => $user->isRootAdmin(),
                    'role' => $user->currentSchoolRole(),
                ] : null,
            ],
            'flash' => fn () => [
                'alert' => $request->session()->get('alert'),
                'type' => $request->session()->get('type'),
                'status' => $request->session()->get('status'),
            ],
        ]);
    }
}
