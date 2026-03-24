import { Head, useForm, Link } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

export default function StudentsImport() {
    const { data, setData, post, processing, errors } = useForm<{ csv: File | null }>({
        csv: null,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(route('admin.students.import'));
    }

    return (
        <SchoolLayout>
            <Head title="Import Students" />

            <div className="max-w-lg">
                <div className="flex items-center gap-2 mb-6">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={route('admin.students.index')}>← Back</Link>
                    </Button>
                    <h1 className="text-2xl font-bold">Bulk Import Students</h1>
                </div>

                <div className="rounded-md border bg-muted/30 p-4 mb-6 text-sm">
                    <p className="font-medium mb-1">CSV format</p>
                    <p className="text-muted-foreground">Required columns: <code>name</code>, <code>email</code></p>
                    <p className="text-muted-foreground">Optional columns: <code>year_group</code>, <code>class_name</code></p>
                    <Button variant="link" size="sm" className="px-0 mt-1" asChild>
                        <Link href={route('admin.students.export-template')}>Download template CSV</Link>
                    </Button>
                </div>

                <form onSubmit={handleSubmit} className="grid gap-4">
                    <div className="grid gap-1">
                        <Label htmlFor="csv">CSV File</Label>
                        <Input
                            id="csv"
                            type="file"
                            accept=".csv,text/csv"
                            onChange={e => setData('csv', e.target.files?.[0] ?? null)}
                        />
                        {errors.csv && <p className="text-destructive text-sm">{errors.csv}</p>}
                    </div>
                    <Button type="submit" disabled={processing || data.csv === null}>
                        {processing ? 'Uploading…' : 'Import Students'}
                    </Button>
                </form>
            </div>
        </SchoolLayout>
    );
}
