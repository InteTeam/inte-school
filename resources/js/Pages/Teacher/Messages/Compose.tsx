import { Head, useForm, Link } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';

interface SchoolClass { id: string; name: string; year_group: string }

interface Props {
    classes: SchoolClass[];
}

export default function TeacherCompose({ classes }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        type: 'announcement',
        body: '',
        class_id: '',
        recipient_id: '',
        attachments: [] as File[],
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(route('messages.send'));
    }

    return (
        <SchoolLayout>
            <Head title="Send Message" />

            <div className="max-w-2xl">
                <div className="flex items-center gap-2 mb-6">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={route('messages.index')}>← Back</Link>
                    </Button>
                    <h1 className="text-2xl font-bold">Send Message to Class</h1>
                </div>

                <form onSubmit={handleSubmit} className="grid gap-4">
                    <div className="grid gap-1">
                        <Label>Class</Label>
                        <Select value={data.class_id} onValueChange={v => setData('class_id', v)}>
                            <SelectTrigger><SelectValue placeholder="Select your class…" /></SelectTrigger>
                            <SelectContent>
                                {classes.map(c => (
                                    <SelectItem key={c.id} value={c.id}>{c.name} — {c.year_group}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.class_id && <p className="text-destructive text-sm">{errors.class_id}</p>}
                    </div>

                    <div className="grid gap-1">
                        <Label>Type</Label>
                        <Select value={data.type} onValueChange={v => setData('type', v)}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="announcement">Announcement</SelectItem>
                                <SelectItem value="trip_permission">Trip Permission</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid gap-1">
                        <Label>Message</Label>
                        <Textarea
                            value={data.body}
                            onChange={e => setData('body', e.target.value)}
                            rows={6}
                            placeholder="Write your message…"
                        />
                        {errors.body && <p className="text-destructive text-sm">{errors.body}</p>}
                    </div>

                    <Button type="submit" disabled={processing} className="w-fit">
                        {processing ? 'Sending…' : 'Send to Class'}
                    </Button>
                </form>
            </div>
        </SchoolLayout>
    );
}
