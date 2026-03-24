import { Head, Link, useForm } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/Components/ui/dialog';
import { useState } from 'react';

interface SchoolClass {
    id: string;
    name: string;
    year_group: string;
    teacher: { id: string; name: string } | null;
    students_count: number;
}

interface Props {
    classes: SchoolClass[];
}

export default function ClassesIndex({ classes }: Props) {
    const [open, setOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        year_group: '',
        teacher_id: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(route('admin.classes.store'), {
            onSuccess: () => { reset(); setOpen(false); },
        });
    }

    return (
        <SchoolLayout>
            <Head title="Classes" />

            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold">Classes</h1>
                <Dialog open={open} onOpenChange={setOpen}>
                    <DialogTrigger asChild>
                        <Button>New Class</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Create Class</DialogTitle>
                        </DialogHeader>
                        <form onSubmit={handleSubmit} className="grid gap-4">
                            <div className="grid gap-1">
                                <Label htmlFor="name">Class Name</Label>
                                <Input id="name" value={data.name} onChange={e => setData('name', e.target.value)} placeholder="e.g. Year 3A" />
                                {errors.name && <p className="text-destructive text-sm">{errors.name}</p>}
                            </div>
                            <div className="grid gap-1">
                                <Label htmlFor="year_group">Year Group</Label>
                                <Input id="year_group" value={data.year_group} onChange={e => setData('year_group', e.target.value)} placeholder="e.g. Year 3" />
                                {errors.year_group && <p className="text-destructive text-sm">{errors.year_group}</p>}
                            </div>
                            <Button type="submit" disabled={processing}>Create</Button>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div className="rounded-md border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b bg-muted/50">
                            <th className="px-4 py-3 text-left font-medium">Name</th>
                            <th className="px-4 py-3 text-left font-medium">Year Group</th>
                            <th className="px-4 py-3 text-left font-medium">Teacher</th>
                            <th className="px-4 py-3 text-left font-medium">Students</th>
                            <th className="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        {classes.map(c => (
                            <tr key={c.id} className="border-b last:border-0">
                                <td className="px-4 py-3 font-medium">{c.name}</td>
                                <td className="px-4 py-3 text-muted-foreground">{c.year_group}</td>
                                <td className="px-4 py-3">{c.teacher?.name ?? '—'}</td>
                                <td className="px-4 py-3">{c.students_count}</td>
                                <td className="px-4 py-3 text-right">
                                    <Button variant="ghost" size="sm" asChild>
                                        <Link href={route('admin.classes.show', c.id)}>View</Link>
                                    </Button>
                                </td>
                            </tr>
                        ))}
                        {classes.length === 0 && (
                            <tr>
                                <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">
                                    No classes yet. Create your first class.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </SchoolLayout>
    );
}
