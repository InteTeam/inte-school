import { Head, router } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';

interface Document {
    id: string;
    name: string;
    mime_type: string;
    file_size: number;
    is_parent_facing: boolean;
    is_staff_facing: boolean;
    processing_status: 'pending' | 'processing' | 'indexed' | 'failed';
    created_at: string;
}

interface Props {
    documents: Document[];
}

const STATUS_VARIANTS: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    pending: 'outline',
    processing: 'secondary',
    indexed: 'default',
    failed: 'destructive',
};

function formatBytes(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function DocumentsIndex({ documents }: Props) {
    function handleDelete(id: string) {
        if (!confirm('Delete this document? This cannot be undone.')) return;
        router.delete(route('documents.destroy', id));
    }

    return (
        <SchoolLayout>
            <Head title="Documents" />

            <div className="flex items-center justify-between mb-6">
                <h1 className="text-xl font-semibold">Documents</h1>
                <Button onClick={() => router.visit(route('documents.create'))}>
                    Upload Document
                </Button>
            </div>

            {documents.length === 0 ? (
                <p className="text-sm text-muted-foreground">No documents uploaded yet.</p>
            ) : (
                <div className="rounded-md border overflow-hidden">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="text-left px-4 py-2 font-medium">Name</th>
                                <th className="text-left px-4 py-2 font-medium">Size</th>
                                <th className="text-left px-4 py-2 font-medium">Visibility</th>
                                <th className="text-left px-4 py-2 font-medium">Status</th>
                                <th className="text-left px-4 py-2 font-medium">Uploaded</th>
                                <th className="px-4 py-2" />
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {documents.map(doc => (
                                <tr key={doc.id} className="hover:bg-muted/30">
                                    <td className="px-4 py-3 font-medium truncate max-w-xs">{doc.name}</td>
                                    <td className="px-4 py-3 text-muted-foreground">{formatBytes(doc.file_size)}</td>
                                    <td className="px-4 py-3">
                                        <div className="flex gap-1">
                                            {doc.is_parent_facing && (
                                                <Badge variant="outline" className="text-xs">Parents</Badge>
                                            )}
                                            {doc.is_staff_facing && (
                                                <Badge variant="outline" className="text-xs">Staff</Badge>
                                            )}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge variant={STATUS_VARIANTS[doc.processing_status]}>
                                            {doc.processing_status}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {new Date(doc.created_at).toLocaleDateString()}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive hover:text-destructive"
                                            onClick={() => handleDelete(doc.id)}
                                        >
                                            Delete
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </SchoolLayout>
    );
}
