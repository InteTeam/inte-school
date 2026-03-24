<?php

declare(strict_types=1);

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\School;
use App\Services\DocumentService;
use App\Services\RagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documentService,
        private readonly RagService $ragService,
    ) {}

    /**
     * List school documents — admin/teacher view.
     */
    public function index(): InertiaResponse
    {
        $documents = Document::query()
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'mime_type', 'file_size', 'is_parent_facing', 'is_staff_facing', 'processing_status', 'created_at']);

        return Inertia::render('Admin/Documents/Index', [
            'documents' => $documents,
        ]);
    }

    /**
     * Show the upload form.
     */
    public function create(): InertiaResponse
    {
        return Inertia::render('Admin/Documents/Upload');
    }

    /**
     * Handle document upload. Accepted via POST (file upload).
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (! $user->can('create', Document::class)) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'], // 20 MB cap
            'is_parent_facing' => ['boolean'],
            'is_staff_facing' => ['boolean'],
        ]);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('file');

        $this->documentService->upload($this->currentSchool(), $user, $file, $validated);

        return redirect()->route('documents.index')
            ->with(['alert' => __('documents.uploaded'), 'type' => 'success']);
    }

    /**
     * Soft-delete a document.
     */
    public function destroy(Document $document): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (! $user->can('delete', $document)) {
            abort(403);
        }

        $this->documentService->delete($document);

        return redirect()->route('documents.index')
            ->with(['alert' => __('documents.deleted'), 'type' => 'success']);
    }

    /**
     * RAG query endpoint — returns answer or fallback options.
     * Accessible to parents and students via `feature:rag` gate.
     */
    public function query(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
        ]);

        $school = $this->currentSchool();
        $result = $this->ragService->query($school->id, $validated['question']);

        return response()->json($result);
    }

    private function currentSchool(): School
    {
        /** @var School $school */
        $school = School::find(session('current_school_id'));

        return $school;
    }
}
