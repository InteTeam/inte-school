import { Head, useForm, Link } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
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
interface Staff { id: string; name: string }

interface Props {
    classes: SchoolClass[];
    staff: Staff[];
    thread_id?: string;
}

export default function Compose({ classes, staff, thread_id }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        type: 'announcement',
        body: '',
        recipient_id: '',
        class_id: '',
        thread_id: thread_id ?? '',
        attachments: [] as File[],
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(route('messages.send'));
    }

    return (
        <SchoolLayout>
            <Head title="Compose Message" />

            <div className="max-w-2xl">
                <div className="flex items-center gap-2 mb-6">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={route('messages.index')}>← Back</Link>
                    </Button>
                    <h1 className="text-2xl font-bold">Compose Message</h1>
                </div>

                <form onSubmit={handleSubmit} className="grid gap-4">
                    <div className="grid gap-1">
                        <Label>Message Type</Label>
                        <Select value={data.type} onValueChange={v => setData('type', v)}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="announcement">Announcement</SelectItem>
                                <SelectItem value="attendance_alert">Attendance Alert</SelectItem>
                                <SelectItem value="trip_permission">Trip Permission</SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.type && <p className="text-destructive text-sm">{errors.type}</p>}
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-1">
                            <Label>Send to Class</Label>
                            <Select value={data.class_id} onValueChange={v => setData('class_id', v)}>
                                <SelectTrigger><SelectValue placeholder="Select class…" /></SelectTrigger>
                                <SelectContent>
                                    {classes.map(c => (
                                        <SelectItem key={c.id} value={c.id}>{c.name} — {c.year_group}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-1">
                            <Label>Or individual recipient</Label>
                            <Input
                                placeholder="Recipient ID or email"
                                value={data.recipient_id}
                                onChange={e => setData('recipient_id', e.target.value)}
                            />
                        </div>
                    </div>
                    {errors.recipient_id && <p className="text-destructive text-sm">{errors.recipient_id}</p>}

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

                    <div className="grid gap-1">
                        <Label>Attachments (optional)</Label>
                        <Input
                            type="file"
                            multiple
                            accept="image/jpeg,image/png,image/webp,application/pdf"
                            onChange={e => setData('attachments', Array.from(e.target.files ?? []))}
                        />
                        <p className="text-xs text-muted-foreground">Up to 5 files. JPG, PNG, WebP or PDF. Max 10 MB each.</p>
                    </div>

                    <Button type="submit" disabled={processing} className="w-fit">
                        {processing ? 'Sending…' : 'Send Message'}
                    </Button>
                </form>
            </div>
        </SchoolLayout>
    );
}
