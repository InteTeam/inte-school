import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/Components/ui/button';

interface Document {
    id: string;
    type: string;
    content: string;
    version: string;
}

interface Props {
    documents: Document[];
}

export default function Accept({ documents }: Props) {
    const { t } = useTranslation();
    const [expandedDoc, setExpandedDoc] = useState<string | null>(null);
    const { post, processing } = useForm({
        document_ids: documents.map((d) => d.id),
    });

    return (
        <div className="min-h-screen bg-background px-4 py-8">
            <div className="mx-auto max-w-2xl">
                <Head title={t('legal.accept.title')} />
                <h1 className="mb-2 text-2xl font-bold">{t('legal.accept.title')}</h1>
                <p className="mb-6 text-foreground/70">{t('legal.accept.description')}</p>

                <div className="mb-6 flex flex-col gap-4">
                    {documents.map((doc) => (
                        <div key={doc.id} className="rounded-lg border bg-card">
                            <button
                                type="button"
                                className="flex w-full items-center justify-between px-4 py-3 text-left"
                                onClick={() => setExpandedDoc(expandedDoc === doc.id ? null : doc.id)}
                            >
                                <span className="font-medium">{t(`legal.types.${doc.type}`)}</span>
                                <span className="text-xs text-foreground/50">
                                    {t('legal.version')} {doc.version}
                                </span>
                            </button>
                            {expandedDoc === doc.id && (
                                <div
                                    className="prose prose-sm max-w-none border-t px-4 py-4"
                                    dangerouslySetInnerHTML={{ __html: doc.content }}
                                />
                            )}
                        </div>
                    ))}
                </div>

                <form onSubmit={(e) => { e.preventDefault(); post('/legal/accept'); }}>
                    <p className="mb-4 text-sm text-foreground/70">{t('legal.accept.agreement_text')}</p>
                    <Button type="submit" disabled={processing} className="w-full">
                        {processing ? t('legal.accept.accepting') : t('legal.accept.accept_all')}
                    </Button>
                </form>
            </div>
        </div>
    );
}
