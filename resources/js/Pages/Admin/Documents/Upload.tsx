import { Head, router, useForm } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { FormEvent } from 'react';

interface FormData {
    name: string;
    file: File | null;
    is_parent_facing: boolean;
    is_staff_facing: boolean;
    [key: string]: string | boolean | File | null;
}

export default function DocumentUpload() {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        name: '',
        file: null,
        is_parent_facing: true,
        is_staff_facing: true,
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        // Use router.post for file uploads (Inertia v2 convention)
        router.post(route('documents.store'), data as unknown as Record<string, unknown>, {
            forceFormData: true,
        });
    }

    return (
        <SchoolLayout>
            <Head title="Upload Document" />

            <div className="max-w-lg">
                <div className="flex items-center gap-3 mb-6">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => router.visit(route('documents.index'))}
                    >
                        ← Back
                    </Button>
                    <h1 className="text-xl font-semibold">Upload Document</h1>
                </div>

                <form onSubmit={handleSubmit} className="space-y-5">
                    <div className="space-y-1.5">
                        <label className="text-sm font-medium" htmlFor="name">
                            Display name <span className="text-muted-foreground">(optional)</span>
                        </label>
                        <input
                            id="name"
                            type="text"
                            className="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            placeholder="e.g. Parent Handbook 2025–26"
                            value={data.name}
                            onChange={e => setData('name', e.target.value)}
                        />
                        {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                    </div>

                    <div className="space-y-1.5">
                        <label className="text-sm font-medium" htmlFor="file">
                            PDF file <span className="text-destructive">*</span>
                        </label>
                        <input
                            id="file"
                            type="file"
                            accept="application/pdf,.pdf"
                            className="w-full text-sm"
                            onChange={e => setData('file', e.target.files?.[0] ?? null)}
                        />
                        <p className="text-xs text-muted-foreground">PDF only · max 20 MB</p>
                        {errors.file && <p className="text-xs text-destructive">{errors.file}</p>}
                    </div>

                    <div className="space-y-2">
                        <p className="text-sm font-medium">Visibility</p>
                        <label className="flex items-center gap-2 text-sm cursor-pointer">
                            <input
                                type="checkbox"
                                checked={data.is_parent_facing}
                                onChange={e => setData('is_parent_facing', e.target.checked)}
                                className="rounded"
                            />
                            Visible to parents
                        </label>
                        <label className="flex items-center gap-2 text-sm cursor-pointer">
                            <input
                                type="checkbox"
                                checked={data.is_staff_facing}
                                onChange={e => setData('is_staff_facing', e.target.checked)}
                                className="rounded"
                            />
                            Visible to staff
                        </label>
                    </div>

                    <Button type="submit" disabled={processing} className="w-full">
                        {processing ? 'Uploading…' : 'Upload Document'}
                    </Button>
                </form>
            </div>
        </SchoolLayout>
    );
}
