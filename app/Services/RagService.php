<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DocumentChunk;
use App\Models\Scopes\SchoolScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Retrieval-Augmented Generation service.
 * Embeds query → cosine similarity search (school-scoped) → threshold check → generate answer.
 *
 * Fallback: if Ollama unreachable or score below threshold → return two-choice options.
 */
final class RagService
{
    private const SIMILARITY_THRESHOLD = 0.70;

    private const MAX_CHUNKS = 5;

    public function __construct(
        private readonly OllamaService $ollama,
    ) {}

    /**
     * Query the school's knowledge base.
     *
     * Returns one of:
     * - `['type' => 'answer', 'text' => '...']` — answer above threshold
     * - `['type' => 'fallback', 'options' => ['contact_school', 'create_ticket']]` — no confident answer
     *
     * Never throws.
     *
     * @return array{type: string, text?: string, options?: string[]}
     */
    public function query(string $schoolId, string $question): array
    {
        try {
            $embedding = $this->ollama->embed($question);

            if ($embedding === null) {
                return $this->fallback();
            }

            $chunks = $this->similaritySearch($schoolId, $embedding);

            if (empty($chunks)) {
                return $this->fallback();
            }

            $answer = $this->ollama->generate($question, $chunks);

            if ($answer === null) {
                return $this->fallback();
            }

            return ['type' => 'answer', 'text' => $answer];
        } catch (\Throwable $e) {
            Log::warning('RagService query exception — graceful return', ['error' => $e->getMessage()]);

            return $this->fallback();
        }
    }

    /**
     * @param  float[]  $embedding
     * @return array<int, string>
     */
    private function similaritySearch(string $schoolId, array $embedding): array
    {
        $vectorLiteral = '[' . implode(',', $embedding) . ']';

        // Check DB driver — skip cosine search in SQLite (test environment)
        if (DB::getDriverName() !== 'pgsql') {
            return DocumentChunk::withoutGlobalScope(SchoolScope::class)
                ->where('school_id', $schoolId)
                ->limit(self::MAX_CHUNKS)
                ->pluck('content')
                ->all();
        }

        $rows = DB::table('document_chunks')
            ->where('school_id', $schoolId)
            ->orderByRaw("embedding <=> ?::vector", [$vectorLiteral])
            ->limit(self::MAX_CHUNKS)
            ->select('content', DB::raw("1 - (embedding <=> '{$vectorLiteral}'::vector) as similarity"))
            ->get();

        return $rows
            ->filter(fn ($row): bool => (float) $row->similarity >= self::SIMILARITY_THRESHOLD)
            ->pluck('content')
            ->all();
    }

    /** @return array{type: string, options: string[]} */
    private function fallback(): array
    {
        return [
            'type' => 'fallback',
            'options' => ['contact_school', 'create_ticket'],
        ];
    }
}
