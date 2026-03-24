import { Head } from '@inertiajs/react';
import ParentLayout from '@/layouts/ParentLayout';
import { Button } from '@/Components/ui/button';
import { FormEvent, useState } from 'react';

interface FallbackResult {
    type: 'fallback';
    options: string[];
}

interface AnswerResult {
    type: 'answer';
    text: string;
}

type QueryResult = AnswerResult | FallbackResult | null;

export default function ParentAsk() {
    const [question, setQuestion] = useState('');
    const [result, setResult] = useState<QueryResult>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    async function handleSubmit(e: FormEvent) {
        e.preventDefault();
        if (!question.trim()) return;

        setLoading(true);
        setError(null);
        setResult(null);

        try {
            const csrfMeta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
            const csrfToken = csrfMeta?.content ?? '';

            const res = await fetch(route('documents.query'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ question }),
            });

            const data = await res.json() as QueryResult;
            setResult(data);
        } catch {
            setError('Something went wrong. Please try again.');
        } finally {
            setLoading(false);
        }
    }

    const FALLBACK_LABELS: Record<string, string> = {
        contact_school: 'Contact the school office',
        create_ticket: 'Submit a support request',
    };

    return (
        <ParentLayout>
            <Head title="Ask a Question" />

            <div className="max-w-xl mx-auto px-4 py-8">
                <h1 className="text-xl font-semibold mb-1">Ask a Question</h1>
                <p className="text-sm text-muted-foreground mb-6">
                    Ask anything about the school — we'll search our documents to find an answer.
                </p>

                <form onSubmit={handleSubmit} className="space-y-3">
                    <textarea
                        className="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                        rows={3}
                        placeholder="e.g. What time does school start?"
                        maxLength={500}
                        value={question}
                        onChange={e => setQuestion(e.target.value)}
                    />
                    <Button type="submit" disabled={loading || !question.trim()} className="w-full">
                        {loading ? 'Searching…' : 'Ask'}
                    </Button>
                </form>

                {error && (
                    <p className="mt-4 text-sm text-destructive">{error}</p>
                )}

                {result !== null && (
                    <div className="mt-6 rounded-md border p-4">
                        {result.type === 'answer' ? (
                            <>
                                <p className="text-xs text-muted-foreground mb-2 uppercase tracking-wide">Answer</p>
                                <p className="text-sm whitespace-pre-wrap">{result.text}</p>
                            </>
                        ) : (
                            <>
                                <p className="text-sm text-muted-foreground mb-3">
                                    We couldn't find a confident answer in our documents. You can:
                                </p>
                                <div className="flex flex-col gap-2">
                                    {result.options.map(option => (
                                        <Button key={option} variant="outline" className="justify-start">
                                            {FALLBACK_LABELS[option] ?? option}
                                        </Button>
                                    ))}
                                </div>
                            </>
                        )}
                    </div>
                )}
            </div>
        </ParentLayout>
    );
}
