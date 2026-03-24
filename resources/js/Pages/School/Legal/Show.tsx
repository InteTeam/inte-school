import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

interface Document {
    id: string;
    type: string;
    content: string;
    version: string;
    published_at: string;
}

interface Props {
    document: Document;
}

export default function LegalShow({ document }: Props) {
    const { t } = useTranslation();

    return (
        <div className="min-h-screen bg-background px-4 py-8">
            <div className="mx-auto max-w-3xl">
                <Head title={t(`legal.types.${document.type}`)} />
                <div className="mb-4 flex items-center justify-between">
                    <h1 className="text-2xl font-bold">{t(`legal.types.${document.type}`)}</h1>
                    <span className="text-sm text-foreground/50">
                        {t('legal.version')} {document.version}
                    </span>
                </div>
                <div
                    className="prose prose-sm max-w-none rounded-lg border bg-card p-6"
                    dangerouslySetInnerHTML={{ __html: document.content }}
                />
            </div>
        </div>
    );
}
