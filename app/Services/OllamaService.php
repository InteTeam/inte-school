<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Talks to a local Ollama instance for embeddings and RAG generation.
 * All methods return null on failure — never throws.
 */
final class OllamaService
{
    private string $baseUrl;

    private string $embedModel;

    private string $generateModel;

    public function __construct()
    {
        $this->baseUrl = (string) config('services.ollama.url', 'http://localhost:11434');
        $this->embedModel = (string) config('services.ollama.embed_model', 'nomic-embed-text');
        $this->generateModel = (string) config('services.ollama.generate_model', 'llama3.2');
    }

    /**
     * Generate a 768-dimensional embedding for the given text.
     * Returns null if Ollama is unreachable or returns an error.
     *
     * @return float[]|null
     */
    public function embed(string $text): ?array
    {
        try {
            $response = Http::timeout(30)
                ->post("{$this->baseUrl}/api/embeddings", [
                    'model' => $this->embedModel,
                    'prompt' => $text,
                ]);

            if (! $response->successful()) {
                Log::warning('Ollama embed failed', ['status' => $response->status()]);

                return null;
            }

            /** @var array{embedding?: float[]} $data */
            $data = $response->json();

            return $data['embedding'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('Ollama embed exception — graceful return', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Generate a natural language answer from a prompt + context chunks.
     * Returns null if Ollama is unreachable or returns an error.
     *
     * @param  array<int, string>  $contextChunks
     */
    public function generate(string $question, array $contextChunks): ?string
    {
        $context = implode("\n\n---\n\n", $contextChunks);
        $prompt = <<<PROMPT
You are a helpful school information assistant. Answer the question using ONLY the context provided below.
If the context does not contain enough information to answer, say "I don't have enough information to answer that."

Context:
{$context}

Question: {$question}

Answer:
PROMPT;

        try {
            $response = Http::timeout(60)
                ->post("{$this->baseUrl}/api/generate", [
                    'model' => $this->generateModel,
                    'prompt' => $prompt,
                    'stream' => false,
                ]);

            if (! $response->successful()) {
                Log::warning('Ollama generate failed', ['status' => $response->status()]);

                return null;
            }

            /** @var array{response?: string} $data */
            $data = $response->json();

            return $data['response'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('Ollama generate exception — graceful return', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
