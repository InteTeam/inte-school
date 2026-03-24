import { Head, router } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

interface FeatureRequest {
    id: string;
    title: string;
    body: string;
    status: string;
    created_at: string;
    submitter: { id: string; name: string } | null;
    school: { id: string; name: string; slug: string } | null;
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

const STATUSES = ['open', 'under_review', 'planned', 'done', 'declined'] as const;

export default function RootAdminFeatureRequests({ requests }: Props) {
    function updateStatus(id: string, status: string) {
        router.patch(route('root-admin.feature-requests.update-status', id), { status });
    }

    return (
        <SchoolLayout>
            <Head title="Feature Requests — All Schools" />

            <div className="mb-6">
                <h1 className="text-xl font-semibold">Feature Requests</h1>
                <p className="text-sm text-muted-foreground mt-0.5">
                    All requests across all schools · {requests.length} total
                </p>
            </div>

            {requests.length === 0 ? (
                <p className="text-sm text-muted-foreground">No feature requests yet.</p>
            ) : (
                <div className="space-y-3">
                    {requests.map(req => (
                        <div key={req.id} className="rounded-md border p-4">
                            <div className="flex items-start justify-between gap-4 mb-1">
                                <div className="flex items-center gap-2 flex-wrap">
                                    <h3 className="text-sm font-medium">{req.title}</h3>
                                    {req.school && (
                                        <Badge variant="outline" className="text-xs">
                                            {req.school.name}
                                        </Badge>
                                    )}
                                </div>
                                <Badge
                                    variant={STATUS_VARIANTS[req.status] ?? 'outline'}
                                    className="shrink-0"
                                >
                                    {req.status.replace('_', ' ')}
                                </Badge>
                            </div>

                            <p className="text-sm text-muted-foreground whitespace-pre-wrap line-clamp-3 mb-3">
                                {req.body}
                            </p>

                            <div className="flex items-center justify-between">
                                <p className="text-xs text-muted-foreground">
                                    {new Date(req.created_at).toLocaleDateString()}
                                    {req.submitter && ` · ${req.submitter.name}`}
                                </p>

                                {/* Status update inline */}
                                <div className="flex gap-1">
                                    {STATUSES.filter(s => s !== req.status).map(s => (
                                        <Button
                                            key={s}
                                            variant="ghost"
                                            size="sm"
                                            className="text-xs h-7 px-2"
                                            onClick={() => updateStatus(req.id, s)}
                                        >
                                            → {s.replace('_', ' ')}
                                        </Button>
                                    ))}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </SchoolLayout>
    );
}
