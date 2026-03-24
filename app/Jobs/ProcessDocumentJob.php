<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Document;
use App\Models\DocumentChunk;
use App\Services\OllamaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Extracts text from a PDF, splits into ~500-token chunks (≈2000 chars),
 * embeds each chunk via OllamaService and stores DocumentChunk rows.
 *
 * Fallback: on any failure, processing_status → 'failed'.
 */
class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** One attempt only — do not retry on Ollama unreachability. */
    public int $tries = 1;

    private const CHUNK_SIZE = 2000;

    private const CHUNK_OVERLAP = 200;

    public function __construct(
        public readonly Document $document,
    ) {}

    public function handle(OllamaService $ollama): void
    {
        $this->document->update(['processing_status' => 'processing']);

        try {
            $text = $this->extractText($this->document->file_path);

            if ($text === '') {
                Log::warning('ProcessDocumentJob: extracted empty text', [
                    'document_id' => $this->document->id,
                ]);
                $this->document->update(['processing_status' => 'failed']);

                return;
            }

            $chunks = $this->chunkText($text);

            foreach ($chunks as $index => $chunkContent) {
                $embedding = $ollama->embed($chunkContent);

                if ($embedding === null) {
                    Log::warning('ProcessDocumentJob: embed returned null', [
                        'document_id' => $this->document->id,
                        'chunk_index' => $index,
                    ]);
                    continue;
                }

                DocumentChunk::forceCreate([
                    'school_id' => $this->document->school_id,
                    'document_id' => $this->document->id,
                    'chunk_index' => $index,
                    'content' => $chunkContent,
                    'embedding' => '[' . implode(',', $embedding) . ']',
                ]);
            }

            $this->document->update(['processing_status' => 'indexed']);
        } catch (\Throwable $e) {
            Log::error('ProcessDocumentJob: processing failed', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
            ]);
            $this->document->update(['processing_status' => 'failed']);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessDocumentJob permanently failed', [
            'document_id' => $this->document->id,
            'error' => $exception->getMessage(),
        ]);

        $this->document->update(['processing_status' => 'failed']);
    }

    /**
     * Basic PDF text extraction — parses content streams from the PDF binary.
     * Works for machine-generated (non-scanned) PDFs. MVP quality.
     */
    private function extractText(string $filePath): string
    {
        $disk = config('filesystems.default', 'local');
        $content = Storage::disk($disk)->get($filePath);

        if ($content === null) {
            return '';
        }

        // Extract text from BT...ET (Begin Text / End Text) blocks
        $text = '';
        preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $blocks);

        foreach ($blocks[1] as $block) {
            // Parentheses-delimited strings: (text) Tj or (text) TJ
            preg_match_all('/\(([^)]*)\)\s*T[Jj]/', $block, $strings);
            foreach ($strings[1] as $str) {
                $text .= $str . ' ';
            }

            // Hex-encoded strings: <hex> Tj
            preg_match_all('/<([0-9a-fA-F]+)>\s*T[Jj]/', $block, $hexStrings);
            foreach ($hexStrings[1] as $hex) {
                if (strlen($hex) % 2 === 0) {
                    $text .= hex2bin($hex) . ' ';
                }
            }
        }

        // Normalise whitespace
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * Split text into overlapping chunks of ≈CHUNK_SIZE characters.
     *
     * @return array<int, string>
     */
    private function chunkText(string $text): array
    {
        $chunks = [];
        $length = strlen($text);
        $start = 0;

        while ($start < $length) {
            $chunk = substr($text, $start, self::CHUNK_SIZE);
            $chunks[] = trim($chunk);
            $start += self::CHUNK_SIZE - self::CHUNK_OVERLAP;
        }

        return array_filter($chunks, fn (string $c): bool => $c !== '');
    }
}
