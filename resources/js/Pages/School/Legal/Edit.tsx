import { Head, router } from '@inertiajs/react';
import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Placeholder from '@tiptap/extension-placeholder';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

interface Document {
    id: string;
    type: string;
    content: string;
    version: string;
    is_published: boolean;
}

interface Props {
    document: Document;
}

export default function LegalEdit({ document }: Props) {
    const { t } = useTranslation();
    const [publishVersion, setPublishVersion] = useState('');
    const [saving, setSaving] = useState(false);
    const [publishing, setPublishing] = useState(false);

    const editor = useEditor({
        extensions: [
            StarterKit,
            Placeholder.configure({
                placeholder: t('legal.edit.content_placeholder'),
            }),
        ],
        content: document.content,
    });

    const handleSave = () => {
        if (!editor) return;
        setSaving(true);
        router.post(
            `/legal/${document.id}`,
            { content: editor.getHTML(), _method: 'PUT' },
            { onFinish: () => setSaving(false) }
        );
    };

    const handlePublish = () => {
        if (!publishVersion.trim()) return;
        setPublishing(true);
        router.post(
            `/legal/${document.id}/publish`,
            { version: publishVersion },
            { onFinish: () => setPublishing(false) }
        );
    };

    return (
        <div className="min-h-screen bg-background px-4 py-8">
            <div className="mx-auto max-w-3xl">
                <Head title={t('legal.edit.title', { type: t(`legal.types.${document.type}`) })} />
                <h1 className="mb-6 text-2xl font-bold">
                    {t('legal.edit.title', { type: t(`legal.types.${document.type}`) })}
                </h1>

                <div className="mb-4 rounded-lg border bg-card">
                    {/* Toolbar hint */}
                    <div className="border-b px-4 py-2 text-xs text-foreground/50">
                        {t('legal.edit.toolbar_hint')}
                    </div>
                    <div className="min-h-64 p-4">
                        <EditorContent editor={editor} className="prose prose-sm max-w-none" />
                    </div>
                </div>

                <div className="flex items-end gap-4">
                    <Button onClick={handleSave} disabled={saving} variant="outline">
                        {saving ? t('common.saving') : t('common.save_draft')}
                    </Button>

                    <div className="flex flex-1 items-end gap-2">
                        <div className="flex-1">
                            <Label htmlFor="version">{t('legal.publish_version')}</Label>
                            <Input
                                id="version"
                                value={publishVersion}
                                onChange={(e) => setPublishVersion(e.target.value)}
                                placeholder="1.0"
                                className="mt-1"
                            />
                        </div>
                        <Button onClick={handlePublish} disabled={publishing || !publishVersion.trim()}>
                            {publishing ? t('common.publishing') : t('legal.publish')}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}
