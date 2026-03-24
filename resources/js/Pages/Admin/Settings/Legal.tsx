import { Head, Link } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';

interface LegalDocument {
    id: string;
    type: 'privacy_policy' | 'terms_conditions';
    version: string;
    is_published: boolean;
    published_at: string | null;
    edit_url: string;
}

interface Props {
    documents: LegalDocument[];
}

const typeLabel: Record<string, string> = {
    privacy_policy: 'Privacy Policy',
    terms_conditions: 'Terms & Conditions',
};

export default function SettingsLegal({ documents }: Props) {
    return (
        <SchoolLayout>
            <Head title="Legal Documents" />

            <div className="max-w-2xl">
                <h1 className="text-2xl font-bold mb-6">Legal Documents</h1>

                <div className="grid gap-4">
                    {documents.map(doc => (
                        <div key={doc.id} className="rounded-md border p-4 flex items-center justify-between gap-4">
                            <div>
                                <div className="flex items-center gap-2 mb-1">
                                    <span className="font-medium">{typeLabel[doc.type] ?? doc.type}</span>
                                    <Badge variant="outline">v{doc.version}</Badge>
                                    {doc.is_published ? (
                                        <Badge variant="default">Published</Badge>
                                    ) : (
                                        <Badge variant="secondary">Draft</Badge>
                                    )}
                                </div>
                                {doc.published_at && (
                                    <p className="text-xs text-muted-foreground">
                                        Published {new Date(doc.published_at).toLocaleDateString('en-GB')}
                                    </p>
                                )}
                            </div>
                            <Button variant="outline" size="sm" asChild>
                                <Link href={doc.edit_url}>Edit</Link>
                            </Button>
                        </div>
                    ))}

                    {documents.length === 0 && (
                        <p className="text-muted-foreground text-sm py-4">
                            No legal documents found. Complete the onboarding wizard to create them.
                        </p>
                    )}
                </div>
            </div>
        </SchoolLayout>
    );
}
