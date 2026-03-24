import { Head, useForm } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { useState, FormEvent } from 'react';

interface FeatureRequest {
    id: string;
    title: string;
    body: string;
    status: string;
    created_at: string;
    submitter: { id: string; name: string } | null;
}

interface Props {
    requests: FeatureRequest[];
}

const STATUS_VARIANTS: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    open: 'secondary',
    under_review: 'outline',
    planned: 'default',
    done: 'default',
    declined: 'destructive',
};

interface FormData {
    title: string;
    body: string;
    [key: string]: string;
}

export default function FeatureRequestsPage({ requests }: Props) {
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm<FormData>({
        title: '',
        body: '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post(route('admin.settings.feature-requests.store'), {
            onSuccess: () => {
                reset();
                setShowForm(false);
            },
        });
    }

    return (
        <SchoolLayout>
            <Head title="Feature Requests" />

            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-xl font-semibold">Feature Requests</h1>
                    <p className="text-sm text-muted-foreground mt-0.5">
                        Suggest improvements to Inte-School. Our team reviews all requests.
                    </p>
                </div>
                <Button
                    onClick={() => setShowForm(v => !v)}
                    variant={showForm ? 'outline' : 'default'}
                >
                    {showForm ? 'Cancel' : 'New Request'}
                </Button>
            </div>

            {showForm && (
                <form onSubmit={handleSubmit} className="mb-6 rounded-md border p-4 space-y-4 max-w-xl">
                    <h2 className="text-sm font-semibold">Submit a Feature Request</h2>

                    <div className="space-y-1.5">
                        <label className="text-sm font-medium" htmlFor="title">Title</label>
                        <input
                            id="title"
                            type="text"
                            maxLength={150}
                            className="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            placeholder="e.g. Export attendance as CSV"
                            value={data.title}
                            onChange={e => setData('title', e.target.value)}
                        />
                        {errors.title && <p className="text-xs text-destructive">{errors.title}</p>}
                    </div>

                    <div className="space-y-1.5">
                        <label className="text-sm font-medium" htmlFor="body">Details</label>
                        <textarea
                            id="body"
                            rows={5}
                            maxLength={2000}
                            className="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                            placeholder="Describe the feature and the problem it would solve…"
                            value={data.body}
                            onChange={e => setData('body', e.target.value)}
                        />
                        <p className="text-xs text-muted-foreground text-right">
                            {data.body.length}/2000
                        </p>
                        {errors.body && <p className="text-xs text-destructive">{errors.body}</p>}
                    </div>

                    <Button type="submit" disabled={processing}>
                        {processing ? 'Submitting…' : 'Submit Request'}
                    </Button>
                </form>
            )}

            {requests.length === 0 ? (
                <p className="text-sm text-muted-foreground">No feature requests submitted yet.</p>
            ) : (
                <div className="space-y-3">
                    {requests.map(req => (
                        <div key={req.id} className="rounded-md border p-4">
                            <div className="flex items-start justify-between gap-4 mb-2">
                                <h3 className="text-sm font-medium">{req.title}</h3>
                                <Badge variant={STATUS_VARIANTS[req.status] ?? 'outline'} className="shrink-0">
                                    {req.status.replace('_', ' ')}
                                </Badge>
                            </div>
                            <p className="text-sm text-muted-foreground whitespace-pre-wrap line-clamp-3">
                                {req.body}
                            </p>
                            <p className="text-xs text-muted-foreground mt-2">
                                {new Date(req.created_at).toLocaleDateString()}
                                {req.submitter && ` · ${req.submitter.name}`}
                            </p>
                        </div>
                    ))}
                </div>
            )}
        </SchoolLayout>
    );
}
