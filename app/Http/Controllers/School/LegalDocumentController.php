<?php

declare(strict_types=1);

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\SchoolLegalDocument;
use App\Services\LegalDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class LegalDocumentController extends Controller
{
    public function __construct(
        private readonly LegalDocumentService $legalDocumentService,
    ) {}

    public function show(string $type): Response
    {
        $schoolId = session('current_school_id');

        $document = SchoolLegalDocument::where('type', $type)
            ->where('is_published', true)
            ->orderByDesc('published_at')
            ->firstOrFail();

        return Inertia::render('School/Legal/Show', [
            'document' => $document->only('id', 'type', 'content', 'version', 'published_at'),
        ]);
    }

    public function edit(SchoolLegalDocument $document): Response
    {
        if (! auth()->user()->can('update', $document)) {
            abort(403);
        }

        return Inertia::render('School/Legal/Edit', [
            'document' => $document->only('id', 'type', 'content', 'version', 'is_published'),
        ]);
    }

    public function update(Request $request, SchoolLegalDocument $document): RedirectResponse
    {
        if (! auth()->user()->can('update', $document)) {
            abort(403);
        }

        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $document->content = $validated['content'];
        $document->save();

        return back()->with(['alert' => __('legal.saved'), 'type' => 'success']);
    }

    public function publish(Request $request, SchoolLegalDocument $document): RedirectResponse
    {
        if (! auth()->user()->can('update', $document)) {
            abort(403);
        }

        $validated = $request->validate([
            'version' => ['required', 'string', 'max:20'],
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $this->legalDocumentService->publish($document, $user, $validated['version']);

        return back()->with(['alert' => __('legal.published'), 'type' => 'success']);
    }

    public function showAcceptance(): Response
    {
        $schoolId = session('current_school_id');

        $documents = SchoolLegalDocument::where('is_published', true)
            ->orderByDesc('published_at')
            ->get()
            ->groupBy('type')
            ->map(fn ($docs) => $docs->first())
            ->values();

        return Inertia::render('Legal/Accept', [
            'documents' => $documents->map->only('id', 'type', 'content', 'version'),
        ]);
    }

    public function recordAcceptance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'document_ids' => ['required', 'array'],
            'document_ids.*' => ['required', 'string', 'ulid'],
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $schoolId = session('current_school_id');

        $documents = SchoolLegalDocument::whereIn('id', $validated['document_ids'])
            ->where('is_published', true)
            ->get();

        foreach ($documents as $document) {
            $this->legalDocumentService->recordAcceptance($user, $document, $request);
        }

        return redirect()->route('dashboard');
    }
}
