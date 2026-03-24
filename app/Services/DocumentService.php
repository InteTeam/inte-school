<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Handles document upload, storage and deletion.
 * Text extraction and embedding are handled by ProcessDocumentJob.
 */
final class DocumentService
{
    private const ALLOWED_MIME_TYPES = ['application/pdf'];

    public function __construct(
        private readonly StorageService $storageService,
    ) {}

    /**
     * Validate, store and enqueue a document for RAG indexing.
     *
     * @param  array<string, mixed>  $data
     */
    public function upload(School $school, User $uploader, UploadedFile $file, array $data): Document
    {
        // Server-side MIME validation — never trust extension alone
        $mimeType = $file->getMimeType() ?? '';
        if (! in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            abort(422, __('documents.invalid_mime_type'));
        }

        $path = $this->storageService->store(
            $file,
            "schools/{$school->id}/documents"
        );

        $document = Document::forceCreate([
            'school_id' => $school->id,
            'name' => $data['name'] ?? $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $mimeType,
            'file_size' => $file->getSize(),
            'uploaded_by' => $uploader->id,
            'is_parent_facing' => (bool) ($data['is_parent_facing'] ?? true),
            'is_staff_facing' => (bool) ($data['is_staff_facing'] ?? true),
            'processing_status' => 'pending',
        ]);

        ProcessDocumentJob::dispatch($document)->onQueue('default');

        return $document;
    }

    /**
     * Soft-delete a document (chunks cascade via FK).
     * Storage file is retained to allow recovery.
     */
    public function delete(Document $document): void
    {
        Log::info('Document soft-deleted', ['document_id' => $document->id]);
        $document->delete();
    }
}
