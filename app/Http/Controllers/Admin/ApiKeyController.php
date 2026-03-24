<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolApiKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ApiKeyController extends Controller
{
    public function index(): InertiaResponse
    {
        $keys = SchoolApiKey::query()
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'permissions', 'last_used_at', 'expires_at', 'created_at']);

        return Inertia::render('Admin/Settings/ApiKeys', [
            'keys' => $keys,
            // One-time raw key is flashed after generation — cleared after first page load
            'generated_key' => session('generated_key'),
        ]);
    }

    /**
     * Generate a new API key.
     * Raw key is shown ONCE via flash session — never stored in plain text.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', 'in:attendance,messages,homework,users'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $school = $this->currentSchool();

        // Generate 40-char random key — store only the SHA-256 hash
        $rawKey = Str::random(40);

        SchoolApiKey::forceCreate([
            'school_id' => $school->id,
            'name' => $validated['name'],
            'key_hash' => hash('sha256', $rawKey),
            'permissions' => $validated['permissions'],
            'created_by' => $user->id,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return redirect()->route('admin.settings.api-keys')
            ->with('generated_key', $rawKey) // flash — shown once
            ->with(['alert' => __('api_keys.created'), 'type' => 'success']);
    }

    /**
     * Revoke an API key (hard delete — no recovery).
     */
    public function destroy(SchoolApiKey $schoolApiKey): RedirectResponse
    {
        $schoolApiKey->delete();

        return redirect()->route('admin.settings.api-keys')
            ->with(['alert' => __('api_keys.revoked'), 'type' => 'success']);
    }

    private function currentSchool(): School
    {
        /** @var School $school */
        $school = School::find(session('current_school_id'));

        return $school;
    }
}
