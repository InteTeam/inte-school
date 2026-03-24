<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SchoolLegalDocument;
use App\Models\User;
use App\Models\UserLegalAcceptance;
use Illuminate\Http\Request;

final class LegalDocumentService
{
    /** @return array<SchoolLegalDocument> */
    public function getPublishedDocumentsForSchool(string $schoolId): array
    {
        return SchoolLegalDocument::forSchool($schoolId)
            ->where('is_published', true)
            ->orderBy('type')
            ->get()
            ->groupBy('type')
            ->map(fn ($docs) => $docs->sortByDesc('published_at')->first())
            ->values()
            ->toArray();
    }

    public function publish(SchoolLegalDocument $document, User $publisher, string $version): SchoolLegalDocument
    {
        $document->version = $version;
        $document->is_published = true;
        $document->published_at = now();
        $document->published_by = $publisher->id;
        $document->save();

        return $document;
    }

    public function recordAcceptance(
        User $user,
        SchoolLegalDocument $document,
        Request $request
    ): UserLegalAcceptance {
        return UserLegalAcceptance::create([
            'school_id' => $document->school_id,
            'user_id' => $user->id,
            'document_id' => $document->id,
            'document_type' => $document->type,
            'document_version' => $document->version,
            'accepted_at' => now(),
            'ip_address' => $request->ip() ?? '',
            'user_agent' => $request->userAgent() ?? '',
            'created_at' => now(),
        ]);
    }

    public function userNeedsToAccept(User $user, string $schoolId): bool
    {
        $published = SchoolLegalDocument::forSchool($schoolId)
            ->where('is_published', true)
            ->get()
            ->groupBy('type')
            ->map(fn ($docs) => $docs->sortByDesc('published_at')->first());

        foreach ($published as $document) {
            $hasAccepted = UserLegalAcceptance::forSchool($schoolId)
                ->where('user_id', $user->id)
                ->where('document_id', $document->id)
                ->exists();

            if (! $hasAccepted) {
                return true;
            }
        }

        return false;
    }
}
