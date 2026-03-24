import { Head, useForm, Link } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Input } from '@/Components/ui/input';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';

interface SchoolClass {
    id: string;
    name: string;
    year_group: string;
}

interface Props {
    classes: SchoolClass[];
}

export default function HomeworkCreate({ classes }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        class_id: '',
        title: '',
        description: '',
        due_at: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(route('teacher.tasks.homework.store'));
    }

    return (
        <SchoolLayout>
            <Head title="Assign Homework" />

            <div className="max-w-xl">
                <div className="flex items-center gap-2 mb-6">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={route('teacher.tasks.index')}>← Back</Link>
                    </Button>
                    <h1 className="text-2xl font-bold">Assign Homework</h1>
                </div>

                <form onSubmit={handleSubmit} className="grid gap-4">
                    <div className="grid gap-1">
                        <Label>Class</Label>
                        <Select value={data.class_id} onValueChange={v => setData('class_id', v)}>
                            <SelectTrigger><SelectValue placeholder="Select class…" /></SelectTrigger>
                            <SelectContent>
                                {classes.map(c => (
                                    <SelectItem key={c.id} value={c.id}>
                                        {c.name} — {c.year_group}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.class_id && <p className="text-destructive text-sm">{errors.class_id}</p>}
                    </div>

                    <div className="grid gap-1">
                        <Label>Title</Label>
                        <Input
                            value={data.title}
                            onChange={e => setData('title', e.target.value)}
                            placeholder="e.g. Read Chapter 5"
                        />
                        {errors.title && <p className="text-destructive text-sm">{errors.title}</p>}
                    </div>

                    <div className="grid gap-1">
                        <Label>Description <span className="text-muted-foreground text-xs">(optional)</span></Label>
                        <Textarea
                            value={data.description}
                            onChange={e => setData('description', e.target.value)}
                            rows={4}
                        />
                    </div>

                    <div className="grid gap-1">
                        <Label>Due Date</Label>
                        <Input
                            type="datetime-local"
                            value={data.due_at}
                            onChange={e => setData('due_at', e.target.value)}
                        />
                        {errors.due_at && <p className="text-destructive text-sm">{errors.due_at}</p>}
                    </div>

                    <Button type="submit" disabled={processing} className="w-fit">
                        {processing ? 'Assigning…' : 'Assign to Class'}
                    </Button>
                </form>
            </div>
        </SchoolLayout>
    );
}
